<?php

namespace App\Http\Controllers\Recyclage;

use App\Http\Controllers\Controller;
use App\Models\LotTextile;
use App\Models\PasseportNumerique;
use App\Models\Recycleur;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Barryvdh\DomPDF\Facade\Pdf;

class PasseportNumeriqueController extends Controller
{
    /*
    |------------------------------------------------------------------
    | GET /recyclage/lots/{lot}/passeport — Afficher le passeport
    |------------------------------------------------------------------
    */
    public function show(LotTextile $lot): View
    {
        $recycleur = Recycleur::where('user_id', Auth::id())->firstOrFail();
        abort_if($lot->recycleur_id !== $recycleur->id, 403);

        $lot->load(['etapeTraitements', 'passeportNumerique', 'recycleur.user']);

        $passeport = $lot->passeportNumerique;

        return view('recyclage.passeport.show', compact('lot', 'passeport'));
    }

    /*
    |------------------------------------------------------------------
    | POST /recyclage/lots/{lot}/passeport — Générer le passeport QR
    |------------------------------------------------------------------
    */
    public function generer(LotTextile $lot): RedirectResponse
    {
        $recycleur = Recycleur::where('user_id', Auth::id())->firstOrFail();
        abort_if($lot->recycleur_id !== $recycleur->id, 403);
        abort_if($lot->statut !== 'TRAITE', 422, 'Le lot doit être traité avant de générer un passeport.');
        abort_if($lot->passeportNumerique()->exists(), 422, 'Un passeport existe déjà pour ce lot.');

        $passeport = PasseportNumerique::genererPourLot($lot);

        // Marquer le lot comme certifié
        $lot->update(['statut' => 'CERTIFIE']);

        return redirect()
            ->route('recyclage.passeport.show', $lot)
            ->with('success', "Passeport numérique généré — QR : {$passeport->qr_code}");
    }

    /*
    |------------------------------------------------------------------
    | GET /recyclage/lots/{lot}/passeport/pdf — Télécharger en PDF
    |------------------------------------------------------------------
    */
    public function telechargerPdf(LotTextile $lot)
    {
        $recycleur = Recycleur::where('user_id', Auth::id())->firstOrFail();
        abort_if($lot->recycleur_id !== $recycleur->id, 403);
        abort_if(!$lot->passeportNumerique, 404, 'Aucun passeport disponible pour ce lot.');

        $lot->load(['etapeTraitements', 'passeportNumerique', 'recycleur.user']);
        $passeport = $lot->passeportNumerique;

        $pdf = Pdf::loadView('recyclage.passeport.pdf', compact('lot', 'passeport'))
                  ->setPaper('a4', 'portrait');

        return $pdf->download("passeport-{$lot->reference}.pdf");
    }

    /*
    |------------------------------------------------------------------
    | GET /passeport/{qrCode} — Vérification publique par QR code
    |------------------------------------------------------------------
    */
    public function verifier(string $qrCode): View
    {
        $passeport = PasseportNumerique::where('qr_code', $qrCode)
            ->with(['lotTextile.etapeTraitements', 'lotTextile.recycleur.user'])
            ->firstOrFail();

        return view('recyclage.passeport.verifier', compact('passeport'));
    }
}
