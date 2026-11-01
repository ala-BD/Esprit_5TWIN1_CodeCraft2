<?php

namespace App\Http\Resources\Upcycling;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\MessageProjet
 */
class MessageProjetResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'         => $this->id,
            'auteur'     => $this->auteur,          // CLIENT, ATELIER ou IA
            'nom_auteur' => $this->nom_auteur,
            'contenu'    => $this->contenu,
            'cree_le'    => $this->created_at->toIso8601String(),
        ];
    }
}
