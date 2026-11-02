<?php

namespace App\Services;

use App\Services\Upcycling\GeminiClient;

class DonVetementImageService
{
    public function __construct(
        private GeminiClient $gemini,
        private ComfyUiImageClient $comfyUi
    ) {}

    public function estConfigure(string $mode = 'generative'): bool
    {
        return match (config('services.donation_images.provider')) {
            'comfyui' => $this->comfyUi->estConfigure($mode),
            'gemini' => $mode !== 'faithful'
                && $this->gemini->estConfigure()
                && filled(config('services.gemini.image_model')),
            default => false,
        };
    }

    /**
     * @return array{mime_type: string, data: string}
     */
    public function ameliorer(string $chemin, string $mode = 'generative', ?string $type = null): array
    {
        $instruction = 'Extract the garment from this photo. Keep the exact same garment: same color, shape, fabric, '
            .'print, text, logo, seams, and signs of wear. Remove the person and the background. Show only the garment, '
            .'centered on a plain light background. Do not redesign it or add anything.';

        if ($mode === 'faithful' && config('services.donation_images.provider') !== 'comfyui') {
            throw new \RuntimeException('Le détourage fidèle nécessite le fournisseur ComfyUI local.');
        }

        return match (config('services.donation_images.provider')) {
            'comfyui' => $this->comfyUi->generer($instruction, $chemin, $mode, $type),
            'gemini' => $this->gemini->genererImage($instruction, $chemin),
            default => throw new \RuntimeException('Aucun fournisseur de génération d’image valide n’est configuré.'),
        };
    }
}
