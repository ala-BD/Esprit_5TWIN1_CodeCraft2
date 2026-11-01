<?php

namespace App\Http\Resources\Upcycling;

use App\Models\Atelier;
use App\Models\ProjetUpcycling;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Projet d'upcycling. Les relations ne sont incluses que si elles ont été chargées
 * (pas de requêtes cachées) ; le contrôle qualité seulement une fois le projet terminé.
 *
 * @mixin ProjetUpcycling
 */
class ProjetUpcyclingResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'           => $this->id,
            'statut'       => [
                'code'    => $this->statut,
                'libelle' => $this->statut_badge['label'],
            ],
            'progression'  => $this->progression,
            'en_retard'    => $this->en_retard,

            'vetement'     => [
                'type'        => $this->type_vetement,
                'matiere'     => $this->matiere,
                'couleur'     => $this->couleur,
                'etat'        => $this->etat,
                'description' => $this->description,
                'photo_url'   => $this->photo_url,
                'defauts_ia'  => $this->analyse_ia['defauts'] ?? [],
            ],
            'budget_max'   => $this->budget_max,

            'ia'           => [
                'source' => $this->source_ia,
                'idees'  => $this->idee_generee_ia ?? [],
            ],
            'produit'      => [
                'titre'           => $this->produit_final,
                'categorie'       => $this->categorie_produit
                    ? ['code' => $this->categorie_produit, 'libelle' => Atelier::SPECIALITES[$this->categorie_produit] ?? $this->categorie_produit]
                    : null,
                'prix_estime'     => $this->prix_estime_min ? ['min' => $this->prix_estime_min, 'max' => $this->prix_estime_max] : null,
                'photo_resultat'  => $this->photo_resultat_url,
            ],
            'impact'       => [
                'co2_evite_kg'     => $this->co2_evite_kg,
                'eau_economisee_l' => $this->eau_economisee_l,
            ],
            'dates'        => [
                'creation'          => $this->created_at->toIso8601String(),
                'debut_travaux'     => $this->date_debut?->toDateString(),
                'livraison_prevue'  => $this->date_livraison_prevue?->toDateString(),
                'fin'               => $this->date_fin?->toDateString(),
            ],
            'controle_qualite' => $this->when($this->statut === ProjetUpcycling::STATUT_TERMINE, $this->controle_qualite_ia),
            'avis'         => $this->when($this->note_client !== null, fn () => [
                'note'        => $this->note_client,
                'commentaire' => $this->commentaire_client,
            ]),

            'client'       => new UtilisateurResource($this->whenLoaded('client')),
            'atelier'      => new AtelierResource($this->whenLoaded('atelier')),
            'devis'        => DevisResource::collection($this->whenLoaded('devis')),
            'nb_messages'  => $this->whenCounted('messages'),

            'liens'        => [
                'self' => route('api.v1.projets.show', $this->resource),
                'web'  => route('upcycling.projets.show', $this->resource),
            ],
        ];
    }
}
