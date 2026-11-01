<?php

namespace App\Http\Controllers;

use App\Models\DonVetement;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class DonVetementController extends Controller
{
    public function index(): View
    {
        $user = $this->currentUser();
        $donsQuery = DonVetement::with('user')->latest('date_depot');

        if ($user->hasRole(User::ROLE_DONATEUR)) {
            $donsQuery->where('user_id', $user->id);
        } elseif (!$this->canManageDonations()) {
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

    public function store(Request $request): RedirectResponse
    {
        $this->authorizeDonor();

        $validated = $request->validate([
            'type' => ['required', 'string', 'max:100'],
            'matiere' => ['required', 'string', 'max:100'],
            'taille' => ['required', 'string', 'max:50'],
            'etat' => ['required', 'string', 'max:100'],
            'photo_url' => ['nullable', 'url', 'max:2048'],
        ]);

        $don = DonVetement::create([
            ...$validated,
            'user_id' => $request->user()->id,
            'qr_code' => (string) Str::uuid(),
            'statut' => 'DEPOSE',
            'date_depot' => now()->toDateString(),
        ]);

        return redirect()
            ->route('collecte.dons.show', ['don' => $don->id])
            ->with('success', 'Votre don a été enregistré.');
    }

    public function show(DonVetement $don): View
    {
        $user = $this->currentUser();
        $canManage = $this->canManageDonations();

        abort_unless(
            $canManage || ($user->hasRole(User::ROLE_DONATEUR) && $don->user_id === $user->id),
            403
        );

        $don->load('user');

        return view('collecte.dons.show', compact('don', 'canManage'));
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
        abort_unless($this->currentUser()->hasRole(User::ROLE_DONATEUR), 403);
    }

    private function canManageDonations(): bool
    {
        $user = $this->currentUser();

        return $user->hasRole(User::ROLE_COLLECTEUR) || $user->isAdmin();
    }

    private function currentUser(): User
    {
        $user = Auth::user();
        abort_unless($user instanceof User, 403);

        return $user;
    }
}