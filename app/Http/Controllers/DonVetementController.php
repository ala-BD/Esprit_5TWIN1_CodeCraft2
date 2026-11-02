<?php

namespace App\Http\Controllers;

use App\Models\DonVetement;
use App\Models\User;
use App\Services\DonationImageHashService;
use App\Services\DonVetementAiService;
use App\Services\DonVetementDuplicateService;
use App\Services\DonVetementImageService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Throwable;

class DonVetementController extends Controller
{
    public function index(): View
    {
        $user = $this->currentUser();
        $donsQuery = DonVetement::with('user')->latest('date_depot');

        if ($user->aLeRole(User::ROLE_DONATEUR)) {
            $donsQuery->where('user_id', $user->id);
        } elseif (! $this->canManageDonations()) {
            abort(403);
        }

        $dons = $donsQuery->paginate(12);

        return view('collecte.dons.index', compact('dons'));
    }

    public function create(): View
    {
        $this->authorizeDonor();

        return view('collecte.dons.create');
    }

    public function analyserPhoto(Request $request, DonVetementAiService $service): JsonResponse
    {
        $this->authorizeDonor();

        $request->validate([
            'photo' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ], [
            'photo.required' => 'Ajoutez une photo du vêtement.',
            'photo.image' => 'Le fichier doit être une image.',
            'photo.mimes' => 'Formats acceptés : JPG, PNG ou WEBP.',
            'photo.max' => 'La photo ne doit pas dépasser 5 Mo.',
        ]);

        if (! $service->estConfigure()) {
            return response()->json(['message' => "L'analyse IA est indisponible pour le moment."], 503);
        }

        try {
            $suggestion = $service->suggererDepuisPhoto($request->file('photo')->getRealPath());
        } catch (Throwable $e) {
            Log::warning('Don IA : analyse photo échouée.', ['erreur' => $e->getMessage()]);

            return response()->json(['message' => "L'IA n'a pas pu analyser cette photo. Réessayez ou remplissez les champs manuellement."], 502);
        }

        if (! $suggestion['est_vetement']) {
            return response()->json(['message' => "L'IA ne reconnaît pas de vêtement sur cette photo."], 422);
        }

        return response()->json(['suggestion' => $suggestion]);
    }

    public function detecterDoublon(Request $request, DonVetementDuplicateService $service): JsonResponse
    {
        $this->authorizeDonor();

        $validated = $request->validate([
            'photo' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'type' => ['nullable', 'string', 'max:100'],
            'matiere' => ['nullable', 'string', 'max:100'],
            'couleur' => ['nullable', 'string', 'max:100'],
            'taille' => ['nullable', 'string', 'max:50'],
        ]);
        $user = $this->currentUser();
        $photoPath = $request->file('photo')->getRealPath();
        $metadata = collect($validated)->only(['type', 'matiere', 'couleur', 'taille'])->all();
        $result = $service->detecter($user, $photoPath, $metadata);

        $token = Crypt::encryptString(json_encode([
            'user_id' => $user->id,
            'photo_hash' => hash_file('sha256', $photoPath),
            'metadata_hash' => $this->duplicateMetadataHash($metadata),
            'matches' => $result['matches'],
            'ai_status' => $result['ai_status'],
            'issued_at' => now()->timestamp,
        ], JSON_THROW_ON_ERROR));

        return response()->json([
            ...$result,
            'check_token' => $token,
        ]);
    }

    public function ameliorerPhoto(Request $request, DonVetementImageService $service): JsonResponse
    {
        $this->authorizeDonor();
        $user = $this->currentUser();

        $request->validate([
            'photo' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'mode' => ['nullable', Rule::in(['faithful', 'generative'])],
            'type' => ['nullable', 'string', 'max:100'],
        ], [
            'photo.required' => 'Ajoutez une photo du vêtement.',
            'photo.image' => 'Le fichier doit être une image.',
            'photo.mimes' => 'Formats acceptés : JPG, PNG ou WEBP.',
            'photo.max' => 'La photo ne doit pas dépasser 5 Mo.',
        ]);
        $mode = $request->input('mode', 'generative');

        if (! $service->estConfigure($mode)) {
            $message = $mode === 'faithful'
                ? 'Le détourage fidèle nécessite ComfyUI local. Sélectionnez le mode génératif ou configurez ComfyUI.'
                : "L'amélioration d'image IA est indisponible pour le moment.";

            return response()->json(['message' => $message], 503);
        }

        $directory = "don-enhancements/{$user->id}";
        $disk = Storage::disk('local');
        foreach ($disk->files($directory) as $temporaryFile) {
            if ($disk->lastModified($temporaryFile) < now()->subMinutes(30)->timestamp) {
                $disk->delete($temporaryFile);
            }
        }

        try {
            $generated = $service->ameliorer(
                $request->file('photo')->getRealPath(),
                $mode,
                $request->input('type')
            );
            $extension = match ($generated['mime_type']) {
                'image/png' => 'png',
                'image/jpeg' => 'jpg',
                'image/webp' => 'webp',
                default => throw new \RuntimeException('Format d’image généré non pris en charge.'),
            };
            $token = (string) Str::uuid();
            $temporaryPath = "{$directory}/{$token}.{$extension}";

            if (! $disk->put($temporaryPath, $generated['data'])) {
                throw new \RuntimeException('Impossible d’enregistrer temporairement l’image générée.');
            }
        } catch (Throwable $e) {
            Log::warning('Don IA : amélioration photo échouée.', ['erreur' => $e->getMessage()]);

            $message = $mode !== 'faithful'
                ? "L'IA n'a pas pu améliorer cette photo. Réessayez ou conservez l'original."
                : (str_contains($e->getMessage(), 'storyicon/comfyui_segment_anything')
                    || str_starts_with($e->getMessage(), 'Aucun vêtement')
                    ? $e->getMessage()
                    : 'Le détourage fidèle a échoué. Vérifiez que ComfyUI, le node pack Segment Anything et les modèles SAM/GroundingDINO sont disponibles.');

            return response()->json(['message' => $message], 502);
        }

        return response()->json([
            'token' => $token,
            'preview' => 'data:'.$generated['mime_type'].';base64,'.base64_encode($generated['data']),
        ]);
    }

    public function store(Request $request, DonVetementDuplicateService $duplicateService): RedirectResponse
    {
        $this->authorizeDonor();

        $validated = $request->validate([
            'type' => ['required', 'string', 'max:100'],
            'matiere' => ['required', 'string', 'max:100'],
            'couleur' => ['required', 'string', 'max:100'],
            'taille' => ['required', 'string', 'max:50'],
            'etat' => ['required', 'string', 'max:100'],
            'photo' => ['required', 'image', 'mimes:jpeg,png,webp', 'max:5120'],
            'photo_choice' => ['required', Rule::in(['original', 'enhanced'])],
            'enhanced_token' => ['nullable', 'required_if:photo_choice,enhanced', 'uuid'],
            'duplicate_check_token' => ['nullable', 'string', 'max:10000'],
            'duplicate_confirmed' => ['nullable', 'boolean'],
        ]);

        $user = $request->user();
        $metadata = collect($validated)->only(['type', 'matiere', 'couleur', 'taille'])->all();
        $photoPath = $request->file('photo')->getRealPath();
        $duplicateCheck = $this->decodeDuplicateCheck(
            $validated['duplicate_check_token'] ?? null,
            $user,
            $photoPath,
            $metadata
        );
        if ($duplicateCheck === null) {
            if (! empty($validated['duplicate_check_token'])) {
                return back()
                    ->withErrors(['photo' => 'La vérification du doublon a expiré ou les informations ont changé. Relancez la vérification.'])
                    ->withInput($request->except('photo'));
            }

            $duplicateCheck = $duplicateService->detecter($user, $photoPath, $metadata);
        }

        if (
            $duplicateCheck['matches'] !== []
            && ! ($validated['duplicate_confirmed'] ?? false)
        ) {
            return back()
                ->withErrors(['duplicate_confirmation' => 'Un don similaire existe déjà. Confirmez que vous souhaitez tout de même publier cet article.'])
                ->withInput($request->except('photo'));
        }

        $enhancedToken = $validated['enhanced_token'] ?? null;
        $enhancedTemporaryPath = $enhancedToken
            ? $this->enhancedTemporaryPath($user->id, $enhancedToken)
            : null;

        if ($validated['photo_choice'] === 'enhanced' && ! $enhancedTemporaryPath) {
            return back()
                ->withErrors(['enhanced_token' => 'La version améliorée a expiré. Relancez son amélioration avant de valider.'])
                ->withInput($request->except('photo'));
        }

        $originalPhotoPath = $request->file('photo')->store('dons', 'public');
        if ($originalPhotoPath === false) {
            throw new \RuntimeException('Impossible d’enregistrer la photo du don.');
        }

        $enhancedPhotoPath = null;
        if ($enhancedTemporaryPath) {
            $enhancedImage = Storage::disk('local')->get($enhancedTemporaryPath);
            $mimeType = mime_content_type(Storage::disk('local')->path($enhancedTemporaryPath));
            $extension = match ($mimeType) {
                'image/png' => 'png',
                'image/jpeg' => 'jpg',
                'image/webp' => 'webp',
                default => throw new \RuntimeException('Format d’image généré non pris en charge.'),
            };
            $enhancedPhotoPath = 'dons/'.Str::uuid().'.'.$extension;

            if (! Storage::disk('public')->put($enhancedPhotoPath, $enhancedImage)) {
                Storage::disk('public')->delete($originalPhotoPath);
                throw new \RuntimeException('Impossible d’enregistrer la photo améliorée.');
            }
        }

        $selectedPhotoPath = $validated['photo_choice'] === 'enhanced'
            ? $enhancedPhotoPath
            : $originalPhotoPath;

        try {
            $don = DonVetement::create([
                ...collect($validated)->only(['type', 'matiere', 'couleur', 'taille', 'etat'])->all(),
                'user_id' => $user->id,
                'photo_url' => $selectedPhotoPath,
                'photo_originale_url' => $originalPhotoPath,
                'photo_amelioree_url' => $enhancedPhotoPath,
                'photo_hashes' => app(DonationImageHashService::class)->hashes(
                    Storage::disk('public')->path($originalPhotoPath)
                ),
                'qr_code' => (string) Str::uuid(),
                'statut' => 'DEPOSE',
                'date_depot' => now()->toDateString(),
            ]);
        } catch (Throwable $e) {
            Storage::disk('public')->delete(array_filter([$originalPhotoPath, $enhancedPhotoPath]));
            throw $e;
        }

        if ($enhancedTemporaryPath) {
            Storage::disk('local')->delete($enhancedTemporaryPath);
        }

        return redirect()
            ->route('collecte.dons.show', ['don' => $don->id])
            ->with('success', 'Votre don a été enregistré.');
    }

    public function show(DonVetement $don): View
    {
        $user = $this->currentUser();
        $canManage = $this->canManageDonations();

        abort_unless(
            $canManage || ($user->aLeRole(User::ROLE_DONATEUR) && $don->user_id === $user->id),
            403
        );

        $don->load('user');

        return view('collecte.dons.show', compact('don', 'canManage'));
    }

    public function verifier(string $qrCode): View
    {
        $don = DonVetement::where('qr_code', $qrCode)->firstOrFail();

        return view('collecte.dons.verifier', compact('don'));
    }

    public function updateStatut(Request $request, DonVetement $don): RedirectResponse
    {
        abort_unless($this->canManageDonations(), 403);

        $validated = $request->validate([
            'statut' => ['required', Rule::in(['DEPOSE', 'EN_TRI', 'VENDU', 'UPCYCLING', 'RECYCLE'])],
        ]);

        $don->update($validated);

        return redirect()
            ->route('collecte.dons.show', ['don' => $don->id])
            ->with('success', 'Le statut du don a été mis à jour.');
    }

    private function authorizeDonor(): void
    {
        abort_unless($this->currentUser()->aLeRole(User::ROLE_DONATEUR), 403);
    }

    private function canManageDonations(): bool
    {
        $user = $this->currentUser();

        return $user->aLeRole(User::ROLE_COLLECTEUR) || $user->isAdmin();
    }

    private function currentUser(): User
    {
        $user = Auth::user();
        abort_unless($user instanceof User, 403);

        return $user;
    }

    private function enhancedTemporaryPath(int $userId, string $token): ?string
    {
        $disk = Storage::disk('local');
        foreach (['png', 'jpg', 'webp'] as $extension) {
            $path = "don-enhancements/{$userId}/{$token}.{$extension}";
            if ($disk->exists($path)) {
                return $path;
            }
        }

        return null;
    }

    private function decodeDuplicateCheck(?string $token, User $user, string $photoPath, array $metadata): ?array
    {
        if (! $token) {
            return null;
        }

        try {
            $check = json_decode(Crypt::decryptString($token), true, flags: JSON_THROW_ON_ERROR);
        } catch (Throwable) {
            return null;
        }

        if (
            ($check['user_id'] ?? null) !== $user->id
            || ($check['photo_hash'] ?? null) !== hash_file('sha256', $photoPath)
            || ($check['metadata_hash'] ?? null) !== $this->duplicateMetadataHash($metadata)
            || ! is_int($check['issued_at'] ?? null)
            || $check['issued_at'] < now()->subMinutes(15)->timestamp
            || $check['issued_at'] > now()->timestamp
            || ! is_array($check['matches'] ?? null)
        ) {
            return null;
        }

        return [
            'matches' => $check['matches'],
            'ai_status' => $check['ai_status'] ?? 'not_needed',
        ];
    }

    private function duplicateMetadataHash(array $metadata): string
    {
        $orderedMetadata = [];
        foreach (['type', 'matiere', 'couleur', 'taille'] as $field) {
            $orderedMetadata[$field] = trim((string) ($metadata[$field] ?? ''));
        }

        return hash('sha256', json_encode($orderedMetadata, JSON_THROW_ON_ERROR));
    }
}
