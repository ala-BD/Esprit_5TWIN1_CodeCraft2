<?php

namespace App\Http\Resources\Upcycling;

use App\Models\Atelier;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Atelier partenaire (le vecteur d'embedding IA n'est jamais exposé).
 *
 * @mixin Atelier
 */
class AtelierResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'               => $this->id,
            'nom'              => $this->nom,
            'specialite'       => [
                'code'    => $this->specialite,
                'libelle' => Atelier::SPECIALITES[$this->specialite] ?? $this->specialite,
            ],
            'description'      => $this->description,
            'tarif_horaire'    => (float) $this->tarif_horaire,
            'localisation'     => $this->localisation,
            'note_moyenne'     => $this->note_moyenne > 0 ? round($this->note_moyenne, 1) : null,
            'actif'            => $this->actif,
            'photo_url'        => $this->couverture_url,
            'portfolio_url'    => $this->portfolio_url,
            'projets_termines' => $this->whenCounted('projets_termines'),
            'liens'            => [
                'self' => route('api.v1.ateliers.show', $this->resource),
                'web'  => route('upcycling.ateliers.show', $this->resource),
            ],
        ];
    }
}
