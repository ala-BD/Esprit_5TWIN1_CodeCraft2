<?php

namespace App\Http\Controllers\Upcycling;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Upcycling\Concerns\AccesUpcycling;
use App\Models\Devis;
use App\Models\ProjetUpcycling;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DevisController extends Controller
{
    use AccesUpcycling;

    /*
    |------------------------------------------------------------------
    | POST /upcycling/projets/{projet}/devis — L'atelier envoie un devis
    |------------------------------------------------------------------
    */
    public function store(Request $request, ProjetUpcycling $projet): RedirectResponse
    {
        $this->autoriserAtelier($projet);

        if ($projet->statut !== ProjetUpcycling::STATUT_ATELIER_CHOISI) {
            return back()->with('error', "Ce projet n'attend pas de devis.");
        }
        if ($projet->devis()->where('statut', Devis::STATUT_EN_ATTENTE)->exists()) {
            return back()->with('error', 'Un devis est déjà en attente de réponse du client.');
        }

        $request->validate([
            'montant'     => ['required', 'numeric', 'min:1', 'max:100000'],
            'delai_jours' => ['required', 'integer', 'min:1', 'max:180'],
            'message'     => ['nullable', 'string', 'max:1000'],
        ], [
            'montant.required'     => 'Le montant est obligatoire.',
            'montant.numeric'      => 'Le montant doit être un nombre.',
            'montant.min'          => 'Le montant minimum est 1 DT.',
            'delai_jours.required' => 'Le délai est obligatoire.',
            'delai_jours.integer'  => 'Le délai doit être un nombre entier de jours.',
            'delai_jours.min'      => 'Le délai minimum est 1 jour.',
            'delai_jours.max'      => 'Le délai maximum est 180 jours.',
        ]);

        Devis::create([
            'projet_upcycling_id' => $projet->id,
            'montant'             => $request->montant,
            'delai_jours'         => $request->delai_jours,
            'message'             => $request->message,
            'statut'              => Devis::STATUT_EN_ATTENTE,
            'date_emission'       => now(),
        ]);

        return back()->with('success', 'Devis envoyé au client.');
    }

    /*
    |------------------------------------------------------------------
    | PATCH /upcycling/devis/{devis}/accepter — Le client accepte
    |------------------------------------------------------------------
    */
    public function accepter(Devis $devis): RedirectResponse
    {
        $projet = $devis->projetUpcycling;
        $this->autoriserClient($projet);
        abort_unless($devis->statut === Devis::STATUT_EN_ATTENTE, 422, 'Ce devis a déjà reçu une réponse.');

        DB::transaction(function () use ($devis, $projet) {
            $devis->update(['statut' => Devis::STATUT_ACCEPTE]);
            $projet->update([
                'statut'     => ProjetUpcycling::STATUT_DEVIS_ACCEPTE,
                'date_debut' => now(),
            ]);
        });

        return back()->with('success', "Devis accepté ! L'atelier peut commencer les travaux.");
    }

    /*
    |------------------------------------------------------------------
    | PATCH /upcycling/devis/{devis}/refuser — Le client refuse
    |------------------------------------------------------------------
    */
    public function refuser(Devis $devis): RedirectResponse
    {
        $projet = $devis->projetUpcycling;
        $this->autoriserClient($projet);
        abort_unless($devis->statut === Devis::STATUT_EN_ATTENTE, 422, 'Ce devis a déjà reçu une réponse.');

        $devis->update(['statut' => Devis::STATUT_REFUSE]);

        return back()->with('success', "Devis refusé. L'atelier peut vous en proposer un nouveau, ou vous pouvez changer d'atelier.");
    }
}
