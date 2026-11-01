<?php

namespace App\Http\Controllers\Upcycling;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Upcycling\Concerns\AccesUpcycling;
use App\Http\Requests\Upcycling\DevisRequest;
use App\Models\Devis;
use App\Models\ProjetUpcycling;
use App\Services\Upcycling\GestionProjetService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;

class DevisController extends Controller
{
    use AccesUpcycling;

    public function __construct(private GestionProjetService $gestion) {}

    /*
    |------------------------------------------------------------------
    | POST /upcycling/projets/{projet}/devis — L'atelier envoie un devis
    |------------------------------------------------------------------
    */
    public function store(DevisRequest $request, ProjetUpcycling $projet): RedirectResponse
    {
        $this->autoriserAtelier($projet);
        $this->gestion->envoyerDevis($projet, $request->validated());

        return back()->with('success', 'Devis envoyé au client.');
    }

    /*
    |------------------------------------------------------------------
    | PATCH /upcycling/devis/{devis}/accepter — Le client accepte
    |------------------------------------------------------------------
    */
    public function accepter(Devis $devis): RedirectResponse
    {
        $this->autoriserClient($devis->projetUpcycling);
        $this->gestion->accepterDevis($devis);

        return back()->with('success', "Devis accepté ! L'atelier peut commencer les travaux.");
    }

    /*
    |------------------------------------------------------------------
    | PATCH /upcycling/devis/{devis}/refuser — Le client refuse
    |------------------------------------------------------------------
    */
    public function refuser(Devis $devis): RedirectResponse
    {
        $this->autoriserClient($devis->projetUpcycling);
        $this->gestion->refuserDevis($devis);

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
