<?php

namespace App\Http\Controllers;

use App\Models\RechercheSauvegardee;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class RechercheSauvegardeeController extends Controller
{
    /**
     * Sauvegarde les filtres actuels comme une recherche.
     */
    public function store(Request $request): JsonResponse|RedirectResponse
    {
        $request->validate([
            'nom' => ['required', 'string', 'max:100'],
        ]);

        // Les filtres autorisés à sauvegarder
        $allowedKeys = [
            'q', 'categorie', 'taille', 'matiere', 'etat', 'couleur',
            'prix_min', 'prix_max', 'gratuits', 'en_stock', 'vendeur_role',
            'note_min', 'tri',
        ];

        $filtres = array_filter(
            $request->only($allowedKeys),
            fn($v) => $v !== null && $v !== '' && $v !== false && $v !== '0'
        );

        // Normaliser les booléens
        foreach (['gratuits', 'en_stock'] as $k) {
            if (isset($filtres[$k])) {
                $filtres[$k] = filter_var($filtres[$k], FILTER_VALIDATE_BOOLEAN);
            }
        }

        $recherche = RechercheSauvegardee::create([
            'user_id' => $request->user()->id,
            'nom'     => $request->input('nom'),
            'filtres' => $filtres,
        ]);

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => "Recherche «&nbsp;{$recherche->nom}&nbsp;» sauvegardée avec succès.",
                'id'      => $recherche->id,
            ]);
        }

        return redirect()->back()
            ->with('success', "Recherche «&nbsp;{$recherche->nom}&nbsp;» sauvegardée !");
    }

    /**
     * Supprime une recherche sauvegardée.
     */
    public function destroy(Request $request, RechercheSauvegardee $rechercheSauvegardee): JsonResponse|RedirectResponse
    {
        // Only owner can delete
        if ($rechercheSauvegardee->user_id !== $request->user()->id) {
            abort(403);
        }

        $nom = $rechercheSauvegardee->nom;
        $rechercheSauvegardee->delete();

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => "Recherche «&nbsp;{$nom}&nbsp;» supprimée.",
            ]);
        }

        return redirect()->back()->with('info', "Recherche «&nbsp;{$nom}&nbsp;» supprimée.");
    }
}
