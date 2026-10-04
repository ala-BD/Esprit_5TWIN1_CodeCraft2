<?php

namespace App\Http\Controllers;

use App\Http\Requests\AdresseRequest;
use App\Models\Adresse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class AdresseController extends Controller
{
    /**
     * Affiche la liste des adresses de l'utilisateur connecté.
     */
    public function index(Request $request): View
    {
        $adresses = $request->user()
            ->adresses()
            ->orderByDesc('par_defaut')
            ->latest()
            ->get();

        return view('adresses.index', [
            'adresses' => $adresses,
        ]);
    }

    /**
     * Affiche le formulaire de création d'une nouvelle adresse.
     */
    public function create(): View
    {
        $adresse = new Adresse();

        return view('adresses.form', [
            'adresse' => $adresse,
            'isEdit'  => false,
        ]);
    }

    /**
     * Enregistre une nouvelle adresse pour l'utilisateur connecté.
     */
    public function store(AdresseRequest $request): RedirectResponse
    {
        $user = $request->user();
        $validated = $request->validated();

        // Si l'utilisateur n'a pas encore d'adresses, la première devient par défaut automatiquement
        $isFirst = $user->adresses()->count() === 0;
        $makeDefault = $isFirst || !empty($validated['par_defaut']);

        if ($makeDefault) {
            $user->adresses()->update(['par_defaut' => false]);
            $validated['par_defaut'] = true;
        } else {
            $validated['par_defaut'] = false;
        }

        $user->adresses()->create($validated);

        return redirect()
            ->route('adresses.index')
            ->with('success', 'Adresse enregistrée avec succès !');
    }

    /**
     * Affiche le formulaire de modification d'une adresse existante.
     */
    public function edit(Adresse $adresse): View
    {
        Gate::authorize('update', $adresse);

        return view('adresses.form', [
            'adresse' => $adresse,
            'isEdit'  => true,
        ]);
    }

    /**
     * Met à jour une adresse existante.
     */
    public function update(AdresseRequest $request, Adresse $adresse): RedirectResponse
    {
        Gate::authorize('update', $adresse);

        $validated = $request->validated();
        $makeDefault = !empty($validated['par_defaut']);

        if ($makeDefault) {
            $request->user()->adresses()->where('id', '!=', $adresse->id)->update(['par_defaut' => false]);
            $validated['par_defaut'] = true;
        }

        $adresse->update($validated);

        return redirect()
            ->route('adresses.index')
            ->with('success', 'Adresse mise à jour avec succès !');
    }

    /**
     * Définit une adresse comme l'adresse par défaut.
     */
    public function setDefault(Request $request, Adresse $adresse): RedirectResponse
    {
        Gate::authorize('update', $adresse);

        $adresse->makeDefault();

        return redirect()
            ->route('adresses.index')
            ->with('success', "L'adresse « " . ($adresse->libelle ?: $adresse->rue) . " » est désormais votre adresse par défaut.");
    }

    /**
     * Supprime une adresse.
     */
    public function destroy(Adresse $adresse): RedirectResponse
    {
        Gate::authorize('delete', $adresse);

        $user = $adresse->user;
        $wasDefault = $adresse->par_defaut;

        $adresse->delete();

        // Si l'adresse supprimée était par défaut, on promeut la première restante
        if ($wasDefault && $firstRemaining = $user->adresses()->first()) {
            $firstRemaining->update(['par_defaut' => true]);
        }

        return redirect()
            ->route('adresses.index')
            ->with('success', 'Adresse supprimée avec succès.');
    }

    /**
     * Service de géocodage inversé (Proxy sécurisé sans blocage CORS ni restriction navigateur).
     */
    public function reverseGeocode(Request $request): \Illuminate\Http\JsonResponse
    {
        $lat = $request->query('lat');
        $lng = $request->query('lng');

        if (!$lat || !$lng) {
            return response()->json(['error' => 'Coordonnées requises'], 422);
        }

        try {
            $response = \Illuminate\Support\Facades\Http::timeout(5)
                ->withHeaders([
                    'User-Agent'      => 'RetissApp/1.0 (contact@retiss.tn)',
                    'Accept-Language' => 'fr,fr-FR;q=0.9,en;q=0.8',
                ])
                ->get('https://nominatim.openstreetmap.org/reverse', [
                    'format'         => 'json',
                    'lat'            => $lat,
                    'lon'            => $lng,
                    'zoom'           => 18,
                    'addressdetails' => 1,
                ]);

            if ($response->successful()) {
                return response()->json($response->json());
            }
        } catch (\Exception $e) {
            // Silently fall through
        }

        return response()->json(['error' => 'Impossible de résoudre cette adresse'], 502);
    }
}
