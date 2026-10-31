<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ParametreCout;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ParametreCoutController extends Controller
{
    /**
     * Affiche la liste des barèmes configurables par l'administrateur.
     */
    public function index(): View
    {
        $parametres = ParametreCout::orderBy('matiere')->get();

        return view('admin.parametres_cout.index', [
            'parametres' => $parametres,
        ]);
    }

    /**
     * Met à jour un barème de coût spécifique.
     */
    public function update(Request $request, ParametreCout $parametreCout): RedirectResponse
    {
        $validated = $request->validate([
            'cout_recyclage_par_kg'     => ['required', 'numeric', 'min:0'],
            'cout_tri_fixe'             => ['required', 'numeric', 'min:0'],
            'cout_transport_par_kg'     => ['required', 'numeric', 'min:0'],
            'majoration_mixte_pourcent' => ['required', 'numeric', 'min:0', 'max:100'],
            'tarif_horaire_defaut'      => ['required', 'numeric', 'min:0'],
            'marge_cible_pourcent'      => ['required', 'numeric', 'min:0', 'max:100'],
            'marge_minimale_pourcent'   => ['required', 'numeric', 'min:0', 'max:100'],
            'frais_plateforme_pourcent' => ['required', 'numeric', 'min:0', 'max:100'],
            'notes'                     => ['nullable', 'string', 'max:500'],
        ]);

        $parametreCout->update($validated);

        return back()->with('success', "Le barème pour « {$parametreCout->matiere} » a été mis à jour avec succès.");
    }
}

