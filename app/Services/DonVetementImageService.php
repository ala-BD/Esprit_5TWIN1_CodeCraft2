<?php

namespace App\Services;

use App\Services\Upcycling\GeminiClient;

class DonVetementImageService
{
    public function __construct(
        private GeminiClient $gemini,
        private ComfyUiImageClient $comfyUi
    ) {}

    public function estConfigure(): bool
    {
        return match (config('services.donation_images.provider')) {
            'comfyui' => $this->comfyUi->estConfigure(),
            'gemini' => $this->gemini->estConfigure() && filled(config('services.gemini.image_model')),
            default => false,
        };
    }

    /**
     * @return array{mime_type: string, data: string}
     */
    public function ameliorer(string $chemin): array
    {
        $instruction = "Extract the garment from this photo. Keep the exact same garment: same color, shape, fabric, "
            . "print, text, logo, seams, and signs of wear. Remove the person and the background. Show only the garment, "
            . "centered on a plain light background. Do not redesign it or add anything.";

        return match (config('services.donation_images.provider')) {
            'comfyui' => $this->comfyUi->generer($instruction, $chemin),
            'gemini' => $this->gemini->genererImage($instruction, $chemin),
            default => throw new \RuntimeException('Aucun fournisseur de génération d’image valide n’est configuré.'),
        };
    }
}
