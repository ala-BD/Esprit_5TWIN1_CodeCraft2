<?php

namespace App\Http\Resources\Upcycling;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Une ligne du matching : atelier + score sur 100 + détail par critère.
 * La ressource enveloppe le tableau renvoyé par MatchingAtelierService::classer().
 */
class ClassementAtelierResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'rang'    => $this->resource['rang'] ?? null,
            'score'   => $this->resource['score'],
            'details' => $this->resource['details'],
            'atelier' => new AtelierResource($this->resource['atelier']),
        ];
    }
}
