<?php

namespace App\Http\Resources\Upcycling;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\Devis
 */
class DevisResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'            => $this->id,
            'montant'       => (float) $this->montant,
            'devise'        => 'TND',
            'delai_jours'   => $this->delai_jours,
            'message'       => $this->message,
            'statut'        => $this->statut,
            'date_emission' => $this->date_emission->toDateString(),
            'liens'         => [
                'pdf' => route('upcycling.devis.pdf', $this->resource),
            ],
        ];
    }
}
