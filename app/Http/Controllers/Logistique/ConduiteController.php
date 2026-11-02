<?php

namespace App\Http\Controllers\Logistique;

use App\Http\Controllers\Controller;
use App\Models\Mission;
use App\Models\PositionCollecteur;
use App\Models\Tournee;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Mode tournée : l'écran du collecteur sur son téléphone pendant la tournée.
 * Un arrêt à la fois, de grands boutons, et sa position envoyée au dispatch.
 */
class ConduiteController extends Controller
{
    /** Écart minimal entre deux positions enregistrées, en secondes */
    private const INTERVALLE_POSITION = 15;

    /*
    |------------------------------------------------------------------
    | GET /logistique/tournees/{tournee}/conduite — Mode tournée
    |------------------------------------------------------------------
    */
    public function show(Tournee $tournee): View
    {
        $this->autoriser($tournee);

        $tournee->load(['missions.commande.user', 'missions.donVetement.user']);

        return view('logistique.tournees.conduite', [
            'tournee'  => $tournee,
            'prochain' => $this->prochainArret($tournee),
        ]);
    }

    /*
    |------------------------------------------------------------------
    | POST /logistique/missions/{mission}/avancer — Démarrer, terminer ou signaler un échec
    |------------------------------------------------------------------
    */
    public function avancer(Request $request, Mission $mission): JsonResponse|RedirectResponse
    {
        $this->autoriser($mission->tournee);

        $donnees = $request->validate([
            'action' => ['required', Rule::in(['demarrer', 'terminer', 'echouer'])],
            'preuve' => ['nullable', 'string', 'max:255', 'required_if:action,echouer'],
            'photo'  => ['nullable', 'image', 'max:6144'],
        ], [
            'preuve.required_if' => "Indiquez la raison de l'échec.",
            'photo.image'        => 'La preuve doit être une photo.',
            'photo.max'          => 'La photo ne doit pas dépasser 6 Mo.',
        ]);

        $changements = match ($donnees['action']) {
            'demarrer' => ['statut' => Mission::STATUT_EN_COURS],
            'terminer' => ['statut' => Mission::STATUT_TERMINEE],
            'echouer'  => ['statut' => Mission::STATUT_ECHOUEE],
        };

        if (filled($donnees['preuve'] ?? null)) {
            $changements['preuve_livraison'] = $donnees['preuve'];
        }

        if ($request->hasFile('photo')) {
            if ($mission->photo_preuve) {
                Storage::disk('public')->delete($mission->photo_preuve);
            }
            $changements['photo_preuve'] = $request->file('photo')->store('preuves-livraison', 'public');
        }

        // Le reste (commande, don, statut de la tournée, client prévenu) suit via l'observateur
        $mission->update($changements);

        $tournee = $mission->tournee->fresh();
        $prochain = $this->prochainArret($tournee);
        $message = match ($donnees['action']) {
            'demarrer' => "En route vers l'arrêt {$mission->ordre}.",
            'terminer' => "Arrêt {$mission->ordre} terminé.",
            'echouer'  => "Échec noté pour l'arrêt {$mission->ordre}.",
        };

        if ($request->expectsJson()) {
            return response()->json([
                'message'  => $message,
                'termine'  => $prochain === null,
                'prochain' => $prochain?->id,
            ]);
        }

        return redirect()->route('logistique.tournees.conduite', $tournee)->with('success', $message);
    }

    /*
    |------------------------------------------------------------------
    | POST /logistique/position — Position GPS envoyée par le téléphone
    |------------------------------------------------------------------
    */
    public function position(Request $request): JsonResponse
    {
        $donnees = $request->validate([
            'latitude'   => ['required', 'numeric', 'between:-90,90'],
            'longitude'  => ['required', 'numeric', 'between:-180,180'],
            'precision'  => ['nullable', 'numeric', 'min:0'],
            'tournee_id' => ['nullable', 'integer'],
        ]);

        $tourneeId = isset($donnees['tournee_id'])
            ? Tournee::where('user_id', Auth::id())->whereKey($donnees['tournee_id'])->value('id')
            : null;

        // Le téléphone peut en envoyer souvent : on en garde une toutes les 15 secondes
        $derniere = PositionCollecteur::where('user_id', Auth::id())->latest('enregistree_le')->value('enregistree_le');

        if ($derniere && now()->diffInSeconds($derniere, true) < self::INTERVALLE_POSITION) {
            return response()->json(['enregistree' => false]);
        }

        PositionCollecteur::create([
            'user_id'        => Auth::id(),
            'tournee_id'     => $tourneeId,
            'latitude'       => $donnees['latitude'],
            'longitude'      => $donnees['longitude'],
            'precision_m'    => isset($donnees['precision']) ? (int) round($donnees['precision']) : null,
            'enregistree_le' => now(),
        ]);

        return response()->json(['enregistree' => true]);
    }

    /** Arrêt en cours s'il y en a un, sinon le premier arrêt à faire */
    private function prochainArret(Tournee $tournee): ?Mission
    {
        $missions = $tournee->missions;

        return $missions->firstWhere('statut', Mission::STATUT_EN_COURS)
            ?? $missions->firstWhere('statut', Mission::STATUT_A_FAIRE);
    }

    private function autoriser(Tournee $tournee): void
    {
        abort_if($tournee->user_id !== Auth::id(), 403, 'Accès non autorisé.');
    }
}
