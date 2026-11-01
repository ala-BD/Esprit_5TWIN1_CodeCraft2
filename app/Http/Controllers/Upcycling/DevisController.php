<?php

namespace App\Http\Controllers\Upcycling;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Upcycling\Concerns\AccesUpcycling;
use App\Http\Requests\Upcycling\DevisRequest;
use App\Models\Devis;
use App\Models\ProjetUpcycling;
use App\Notifications\UpcyclingNotification;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;

class DevisController extends Controller
{
    use AccesUpcycling;

    /*
    |------------------------------------------------------------------
    | POST /upcycling/projets/{projet}/devis — L'atelier envoie un devis
    |------------------------------------------------------------------
    */
    public function store(DevisRequest $request, ProjetUpcycling $projet): RedirectResponse
    {
        $this->autoriserAtelier($projet);

        if ($projet->statut !== ProjetUpcycling::STATUT_ATELIER_CHOISI) {
            return back()->with('error', "Ce projet n'attend pas de devis.");
        }
        if ($projet->devis()->where('statut', Devis::STATUT_EN_ATTENTE)->exists()) {
            return back()->with('error', 'Un devis est déjà en attente de réponse du client.');
        }

        $devis = Devis::create([
            'projet_upcycling_id' => $projet->id,
            'montant'             => $request->montant,
            'delai_jours'         => $request->delai_jours,
            'message'             => $request->message,
            'statut'              => Devis::STATUT_EN_ATTENTE,
            'date_emission'       => now(),
        ]);

        $projet->client->notify(new UpcyclingNotification(
            $projet,
            "{$projet->atelier->nom} vous propose un devis de " . number_format($devis->montant, 0, ',', ' ')
                . " DT ({$devis->delai_jours} jours) pour « {$projet->produit_final} ».",
            'fa-file-invoice-dollar'
        ));

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

        $projet->atelier->user->notify(new UpcyclingNotification(
            $projet,
            "{$projet->client->full_name} a accepté votre devis pour « {$projet->produit_final} ». Les travaux peuvent commencer !",
            'fa-handshake'
        ));

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

        $projet->atelier->user->notify(new UpcyclingNotification(
            $projet,
            "{$projet->client->full_name} a refusé votre devis de " . number_format($devis->montant, 0, ',', ' ')
                . " DT. Vous pouvez en proposer un nouveau.",
            'fa-file-circle-xmark'
        ));

        return back()->with('success', "Devis refusé. L'atelier peut vous en proposer un nouveau, ou vous pouvez changer d'atelier.");
    }

    /*
    |------------------------------------------------------------------
    | GET /upcycling/devis/{devis}/pdf — Devis PDF (client ou atelier)
    |------------------------------------------------------------------
    */
    public function pdf(Devis $devis): Response
    {
        $projet = $devis->projetUpcycling->load(['client', 'atelier.user']);
        abort_unless($this->estProprietaire($projet) || $this->estAtelierDuProjet($projet), 403);

        $numero = 'DEV-' . $devis->date_emission->format('Y') . '-' . str_pad($devis->id, 4, '0', STR_PAD_LEFT);

        return Pdf::loadView('upcycling.devis.pdf', compact('devis', 'projet', 'numero'))
            ->setPaper('a4', 'portrait')
            ->download("devis-{$numero}.pdf");
    }
}
