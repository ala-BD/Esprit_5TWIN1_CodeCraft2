<?php

namespace App\Services;

use App\Services\Upcycling\GeminiClient;

class DonVetementImageService
{
    public function __construct(private GeminiClient $gemini) {}

    public function estConfigure(): bool
    {
        return $this->gemini->estConfigure() && filled(config('services.gemini.image_model'));
    }

    /**
     * @return array{mime_type: string, data: string}
     */
    public function ameliorer(string $chemin): array
    {
        return $this->gemini->genererImage(
            "Create a clean, realistic e-commerce product photograph of the exact garment shown in the input image. "
            . "Preserve the garment's identity and all visible details: its type, color, cut, material, pattern, "
            . "stitching, labels, logos, and actual wear, stains, or damage. Do not repair, redesign, recolor, "
            . "remove defects, add accessories, add text, or invent details. Only improve the lighting and use "
            . "a simple neutral studio background. Show the complete garment centered, with no person or mannequin. "
            . "The result must accurately represent the donated item, not an idealized replacement.",
            $chemin
        );
    }
}
