<?php

namespace App\Services;

use App\Models\DonVetement;
use App\Models\User;
use App\Services\Upcycling\GeminiClient;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

class DonVetementDuplicateService
{
    private const HASH_DISTANCE_THRESHOLD = 10;
    private const AI_CANDIDATE_LIMIT = 3;
    private const AI_CONFIDENCE_THRESHOLD = 0.65;

    public function __construct(
        private DonationImageHashService $imageHashes,
        private GeminiClient $gemini
    ) {}

    /**
     * @param array{type?: string, matiere?: string, couleur?: string, taille?: string} $metadata
     * @return array{matches: list<array{donation_id: int, type: string, couleur: string, taille: string, date_depot: ?string, detection: string, confidence: float}>, ai_status: string}
     */
    public function detecter(User $donor, string $photoPath, array $metadata): array
    {
        $newHashes = $this->imageHashes->hashes($photoPath);
        $existingDonations = DonVetement::query()
            ->where('user_id', $donor->id)
            ->latest('id')
            ->get();
        $matches = [];
        $candidates = [];

        foreach ($existingDonations as $donation) {
            $existingPhotoPath = $this->localPhotoPath($donation);
            if (!$existingPhotoPath) {
                continue;
            }

            $storedHashes = $donation->photo_hashes;
            if (!is_array($storedHashes) || $storedHashes === []) {
                try {
                    $storedHashes = $this->imageHashes->hashes($existingPhotoPath);
                    $donation->forceFill(['photo_hashes' => $storedHashes])->saveQuietly();
                } catch (Throwable $exception) {
                    Log::warning('Don doublon : impossible de calculer l’empreinte d’une ancienne photo.', [
                        'donation_id' => $donation->id,
                        'error' => $exception->getMessage(),
                    ]);
                    continue;
                }
            }

            $distance = $this->imageHashes->distance($newHashes, $storedHashes);
            if ($distance !== null && $distance <= self::HASH_DISTANCE_THRESHOLD) {
                $matches[] = $this->presentMatch($donation, 'image', max(0.65, 1 - ($distance / 32)));
                continue;
            }

            $similarity = $this->metadataSimilarity($metadata, $donation);
            if ($similarity >= 1.5) {
                $candidates[] = [
                    'donation' => $donation,
                    'path' => $existingPhotoPath,
                    'similarity' => $similarity,
                ];
            }
        }

        if ($matches !== []) {
            return ['matches' => $matches, 'ai_status' => 'not_needed'];
        }

        if ($candidates === [] || !$this->gemini->estConfigure()) {
            return [
                'matches' => [],
                'ai_status' => !$this->gemini->estConfigure() ? 'unavailable' : 'not_needed',
            ];
        }

        usort($candidates, fn (array $first, array $second) => $second['similarity'] <=> $first['similarity']);
        $candidates = array_slice($candidates, 0, self::AI_CANDIDATE_LIMIT);

        try {
            $aiMatches = $this->compareWithGemini($photoPath, $metadata, $candidates);
            foreach ($aiMatches as $aiMatch) {
                $donation = $existingDonations->firstWhere('id', $aiMatch['donation_id']);
                if ($donation && $aiMatch['confidence'] >= self::AI_CONFIDENCE_THRESHOLD) {
                    $matches[] = $this->presentMatch(
                        $donation,
                        'vetement',
                        $aiMatch['confidence']
                    );
                }
            }
        } catch (Throwable $exception) {
            Log::warning('Don doublon : comparaison visuelle Gemini indisponible.', [
                'user_id' => $donor->id,
                'error' => $exception->getMessage(),
            ]);

            return ['matches' => [], 'ai_status' => 'error'];
        }

        return ['matches' => $matches, 'ai_status' => 'checked'];
    }

    private function localPhotoPath(DonVetement $donation): ?string
    {
        $photoPath = $donation->photo_originale_url ?: $donation->photo_url;
        if (!$photoPath || Str::startsWith($photoPath, ['http://', 'https://'])) {
            return null;
        }

        $photoPath = ltrim(Str::after($photoPath, '/storage/'), '/');
        $disk = Storage::disk('public');

        return $disk->exists($photoPath) ? $disk->path($photoPath) : null;
    }

    private function metadataSimilarity(array $metadata, DonVetement $donation): float
    {
        $weights = ['type' => 2.0, 'couleur' => 1.5, 'matiere' => 1.0, 'taille' => 0.75];
        $score = 0.0;

        foreach ($weights as $attribute => $weight) {
            $submitted = $this->normalize((string) ($metadata[$attribute] ?? ''));
            $existing = $this->normalize((string) $donation->{$attribute});
            if ($submitted !== '' && $existing !== '' && (
                $submitted === $existing
                || str_contains($submitted, $existing)
                || str_contains($existing, $submitted)
            )) {
                $score += $weight;
            }
        }

        return $score;
    }

    private function normalize(string $value): string
    {
        return preg_replace('/[^a-z0-9]/', '', Str::ascii(Str::lower(trim($value)))) ?? '';
    }

    /**
     * @param list<array{donation: DonVetement, path: string, similarity: float}> $candidates
     * @return list<array{donation_id: int, confidence: float}>
     */
    private function compareWithGemini(string $photoPath, array $metadata, array $candidates): array
    {
        $temporaryImages = [];
        try {
            $currentImage = $this->prepareImageForGemini($photoPath);
            $temporaryImages[] = $currentImage;
            $parts = [
                ['text' => "Photo actuelle à comparer. Type saisi : " . ($metadata['type'] ?? 'non précisé')
                    . ", couleur : " . ($metadata['couleur'] ?? 'non précisée')
                    . ", matière : " . ($metadata['matiere'] ?? 'non précisée') . '.'],
                $this->gemini->image($currentImage),
            ];
            $candidateIds = [];

            foreach ($candidates as $candidate) {
                $donation = $candidate['donation'];
                $candidateImage = $this->prepareImageForGemini($candidate['path']);
                $temporaryImages[] = $candidateImage;
                $parts[] = ['text' => "Ancien don ID {$donation->id}. Type : {$donation->type}, couleur : "
                    . ($donation->couleur ?: 'non précisée') . ", matière : {$donation->matiere}, taille : {$donation->taille}."];
                $parts[] = $this->gemini->image($candidateImage);
                $candidateIds[] = $donation->id;
            }

            $data = $this->gemini->genererJson(
                "Détermine si la photo actuelle montre exactement le même article physique qu'un ancien don, "
                    . "même avec un angle, éclairage, fond, recadrage ou compression différents. "
                    . "Ne signale pas deux vêtements simplement semblables, de même type ou couleur. "
                    . "Ne retourne que les anciens dons qui représentent probablement le même objet. "
                    . "Le champ confidence est un nombre entre 0 et 1.",
                $parts,
                [
                    'type' => 'OBJECT',
                    'properties' => [
                        'matches' => [
                            'type' => 'ARRAY',
                            'items' => [
                                'type' => 'OBJECT',
                                'properties' => [
                                    'donation_id' => ['type' => 'INTEGER'],
                                    'confidence' => ['type' => 'NUMBER'],
                                ],
                                'required' => ['donation_id', 'confidence'],
                            ],
                        ],
                    ],
                    'required' => ['matches'],
                ]
            );

            return collect($data['matches'] ?? [])
                ->filter(fn (array $match) => in_array((int) ($match['donation_id'] ?? 0), $candidateIds, true))
                ->map(fn (array $match) => [
                    'donation_id' => (int) $match['donation_id'],
                    'confidence' => max(0, min(1, (float) ($match['confidence'] ?? 0))),
                ])
                ->values()
                ->all();
        } finally {
            foreach ($temporaryImages as $temporaryImage) {
                @unlink($temporaryImage);
            }
        }
    }

    private function prepareImageForGemini(string $path): string
    {
        $contents = file_get_contents($path);
        $source = $contents === false ? false : @imagecreatefromstring($contents);
        if ($source === false) {
            throw new \RuntimeException('Impossible de préparer une photo pour la comparaison IA.');
        }

        $width = imagesx($source);
        $height = imagesy($source);
        $scale = min(1, 640 / max($width, $height));
        $resized = imagecreatetruecolor(max(1, (int) round($width * $scale)), max(1, (int) round($height * $scale)));
        $white = imagecolorallocate($resized, 255, 255, 255);
        imagefill($resized, 0, 0, $white);
        imagecopyresampled(
            $resized,
            $source,
            0,
            0,
            0,
            0,
            imagesx($resized),
            imagesy($resized),
            $width,
            $height
        );
        $temporaryPath = tempnam(sys_get_temp_dir(), 'textilecycle-duplicate-');
        if ($temporaryPath === false || !imagejpeg($resized, $temporaryPath, 78)) {
            throw new \RuntimeException('Impossible de préparer une photo pour la comparaison IA.');
        }

        return $temporaryPath;
    }

    private function presentMatch(DonVetement $donation, string $detection, float $confidence): array
    {
        return [
            'donation_id' => $donation->id,
            'type' => $donation->type,
            'couleur' => $donation->couleur ?? '',
            'taille' => $donation->taille,
            'date_depot' => $donation->date_depot?->format('d/m/Y'),
            'detection' => $detection,
            'confidence' => round($confidence, 2),
        ];
    }
}
