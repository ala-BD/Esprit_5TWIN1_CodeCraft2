<?php

namespace App\Http\Controllers\Logistique;

use App\Http\Controllers\Controller;
use App\Models\Commande;
use App\Models\DonVetement;
use App\Models\Mission;
use App\Models\Tournee;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class MissionController extends Controller
{
    /*
    |------------------------------------------------------------------
    | GET /logistique/tournees/{tournee}/missions/create — Formulaire création
    |------------------------------------------------------------------
    */
    public function create(Tournee $tournee): View
    {
        $this->autoriser($tournee);

        $mission = new Mission([
            'type'   => Mission::TYPE_COLLECTE,
            'statut' => Mission::STATUT_A_FAIRE,
            'ordre'  => (int) $tournee->missions()->max('ordre') + 1,
        ]);

        return view('logistique.missions.create', [
            'tournee' => $tournee,
            'mission' => $mission,
            ...$this->options(),
        ]);
    }

    /*
    |------------------------------------------------------------------
    | POST /logistique/tournees/{tournee}/missions — Enregistrer une mission
    |------------------------------------------------------------------
    */
    public function store(Request $request, Tournee $tournee): RedirectResponse
    {
        $this->autoriser($tournee);

        $tournee->missions()->create($this->valider($request));

        return redirect()
            ->route('logistique.tournees.show', $tournee)
            ->with('success', 'Mission ajoutée à la tournée.');
    }

    /*
    |------------------------------------------------------------------
    | GET /logistique/missions/{mission}/edit — Formulaire édition
    |------------------------------------------------------------------
    */
    public function edit(Mission $mission): View
    {
        $this->autoriser($mission->tournee);

        return view('logistique.missions.edit', [
            'tournee' => $mission->tournee,
            'mission' => $mission,
            ...$this->options($mission),
        ]);
    }

    /*
    |------------------------------------------------------------------
    | PUT /logistique/missions/{mission} — Mettre à jour une mission
    |------------------------------------------------------------------
    */
    public function update(Request $request, Mission $mission): RedirectResponse
    {
        $this->autoriser($mission->tournee);

        $mission->update($this->valider($request, $mission));

        return redirect()
            ->route('logistique.tournees.show', $mission->tournee)
            ->with('success', 'Mission mise à jour avec succès.');
    }

    /*
    |------------------------------------------------------------------
    | DELETE /logistique/missions/{mission} — Supprimer une mission
    |------------------------------------------------------------------
    */
    public function destroy(Mission $mission): RedirectResponse
    {
        $this->autoriser($mission->tournee);

        $tournee = $mission->tournee;
        $mission->delete();

        return redirect()
            ->route('logistique.tournees.show', $tournee)
            ->with('success', 'Mission supprimée.');
    }

    /** S'assurer que le collecteur connecté est propriétaire de la tournée */
    private function autoriser(Tournee $tournee): void
    {
        abort_if($tournee->user_id !== Auth::id(), 403, 'Accès non autorisé.');
    }

    /**
     * Commandes à livrer et dons à collecter qui ne sont pas déjà pris en charge
     * par une autre mission active.
     */
    private function options(?Mission $mission = null): array
    {
        $dejaPris = fn (string $colonne) => Mission::whereNotNull($colonne)
            ->where('statut', '!=', Mission::STATUT_ECHOUEE)
            ->when($mission, fn ($q) => $q->where('id', '!=', $mission->id))
            ->pluck($colonne);

        $commandes = Commande::with(['user', 'adresse'])
            ->whereIn('statut', [Commande::STATUT_EN_ATTENTE, Commande::STATUT_CONFIRMEE, Commande::STATUT_EN_PREPARATION, Commande::STATUT_EXPEDIEE])
            ->whereNotIn('id', $dejaPris('commande_id'))
            ->latest('date_commande')->limit(100)->get();

        $dons = DonVetement::with('user.adresseParDefaut')
            ->where('statut', 'DEPOSE')
            ->whereNotIn('id', $dejaPris('don_vetement_id'))
            ->latest()->limit(100)->get();

        // La valeur actuelle reste proposée même si elle ne remplit plus les critères
        if ($mission?->commande_id && !$commandes->contains('id', $mission->commande_id) && $mission->commande) {
            $commandes->prepend($mission->commande->load(['user', 'adresse']));
        }
        if ($mission?->don_vetement_id && !$dons->contains('id', $mission->don_vetement_id) && $mission->donVetement) {
            $dons->prepend($mission->donVetement->load('user.adresseParDefaut'));
        }

        return [
            'commandes' => ['' => 'Aucune commande liée'] + $commandes->mapWithKeys(fn (Commande $c) => [
                $c->id => "{$c->numero} — {$c->user?->full_name}" . ($c->adresse?->ville ? ", {$c->adresse->ville}" : ''),
            ])->all(),
            'dons' => ['' => 'Aucun don lié'] + $dons->mapWithKeys(fn (DonVetement $d) => [
                $d->id => "Don n° {$d->id} — {$d->type}, {$d->user?->full_name}" . ($d->user?->adresseParDefaut?->ville ? ", {$d->user->adresseParDefaut->ville}" : ''),
            ])->all(),
            // Adresses proposées dans le formulaire quand on lie une commande ou un don
            'adressesLiees' => [
                'commande' => $commandes->mapWithKeys(fn (Commande $c) => [$c->id => $c->adresse?->formatted_address ?? (string) $c->adresse_snapshot])->all(),
                'don'      => $dons->mapWithKeys(fn (DonVetement $d) => [$d->id => (string) $d->user?->adresseParDefaut?->formatted_address])->all(),
            ],
        ];
    }

    /*
    |------------------------------------------------------------------
    | Validation commune création / édition
    |------------------------------------------------------------------
    */
    private function valider(Request $request, ?Mission $mission = null): array
    {
        return $request->validate(self::regles($mission), self::MESSAGES);
    }

    /** Règles de validation, partagées avec l'assistant vocal */
    public static function regles(?Mission $mission = null): array
    {
        // Une commande ou un don ne peut être pris en charge que par une seule mission active
        $libre = fn (string $colonne) => Rule::unique('missions', $colonne)
            ->ignore($mission)
            ->where(fn ($q) => $q->where('statut', '!=', Mission::STATUT_ECHOUEE));

        return [
            'type'             => ['required', Rule::in(array_keys(Mission::TYPES))],
            // Facultative quand une commande ou un don est lié : on reprend son adresse
            'adresse'          => ['nullable', 'required_without_all:commande_id,don_vetement_id', 'string', 'max:255'],
            'ordre'            => ['required', 'integer', 'min:1', 'max:999'],
            'heure_prevue'     => ['required', 'date_format:H:i'],
            'statut'           => ['required', Rule::in(array_keys(Mission::STATUTS))],
            'preuve_livraison' => ['nullable', 'string', 'max:255'],
            'don_vetement_id'  => ['nullable', 'integer', 'exists:don_vetements,id', 'prohibited_unless:type,' . Mission::TYPE_COLLECTE, $libre('don_vetement_id')],
            'commande_id'      => ['nullable', 'integer', 'exists:commandes,id', 'prohibited_unless:type,' . Mission::TYPE_LIVRAISON, $libre('commande_id')],
        ];
    }

    /** Messages de validation, partagés avec l'assistant vocal */
    public const MESSAGES = [
        'type.required'            => 'Le type de mission est obligatoire.',
        'type.in'                  => 'Le type sélectionné est invalide.',
        'adresse.required_without_all' => "L'adresse est obligatoire si aucune commande ni aucun don n'est lié.",
        'ordre.required'           => "L'ordre de passage est obligatoire.",
        'ordre.integer'            => "L'ordre de passage doit être un nombre entier.",
        'ordre.min'                => "L'ordre de passage commence à 1.",
        'heure_prevue.required'    => "L'heure prévue est obligatoire.",
        'heure_prevue.date_format' => "L'heure prévue doit être au format HH:MM.",
        'statut.required'          => 'Le statut est obligatoire.',
        'statut.in'                => 'Le statut sélectionné est invalide.',
        'don_vetement_id.exists'   => "Le don sélectionné n'existe pas.",
        'don_vetement_id.prohibited_unless' => 'Un don se lie uniquement à une collecte.',
        'don_vetement_id.unique'   => 'Ce don est déjà pris en charge par une autre mission.',
        'commande_id.exists'       => "La commande sélectionnée n'existe pas.",
        'commande_id.prohibited_unless' => 'Une commande se lie uniquement à une livraison.',
        'commande_id.unique'       => 'Cette commande est déjà prise en charge par une autre mission.',
    ];
}
