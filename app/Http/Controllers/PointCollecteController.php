<?php

namespace App\Http\Controllers;

use App\Models\PointCollecte;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PointCollecteController extends Controller
{
    public function dashboard(): View
    {
        $stats = [
            'total' => PointCollecte::count(),
            'actifs' => PointCollecte::where('statut', 'ACTIF')->count(),
            'inactifs' => PointCollecte::where('statut', 'INACTIF')->count(),
            'pleins' => PointCollecte::where('statut', 'PLEIN')->count(),
            'capacite_totale' => PointCollecte::sum('capacite_max_kg'),
        ];

        $points = PointCollecte::latest()->take(5)->get();

        return view('collecte.dashboard', compact('stats', 'points'));
    }

    /**
     * Display a listing of the resource.
     */
    public function index(): View
    {
        $points = PointCollecte::latest()->paginate(10);

        return view('collecte.points.index', compact('points'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): View
    {
        return view('collecte.points.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'nom' => ['required', 'string', 'max:255'],
            'adresse' => ['required', 'string', 'max:255'],
            'ville' => ['required', 'string', 'max:255'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'capacite_max_kg' => ['required', 'numeric', 'min:0'],
            'telephone' => ['nullable', 'string', 'max:20'],
            'statut' => ['required', 'in:ACTIF,INACTIF,PLEIN'],
            'description' => ['nullable', 'string', 'max:1000'],
        ]);

        $point = PointCollecte::create($validated);

        return redirect()
            ->route('collecte.points.show', $point)
            ->with('success', 'Point de collecte créé avec succès.');
    }

    /**
     * Display the specified resource.
     */
    public function show(PointCollecte $pointCollecte): View
    {
        $pointCollecte->load('gestionnaire');

        return view('collecte.points.show', compact('pointCollecte'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(PointCollecte $pointCollecte): View
    {
        return view('collecte.points.edit', compact('pointCollecte'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, PointCollecte $pointCollecte): RedirectResponse
    {
        $validated = $request->validate([
            'nom' => ['required', 'string', 'max:255'],
            'adresse' => ['required', 'string', 'max:255'],
            'ville' => ['required', 'string', 'max:255'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'capacite_max_kg' => ['required', 'numeric', 'min:0'],
            'telephone' => ['nullable', 'string', 'max:20'],
            'statut' => ['required', 'in:ACTIF,INACTIF,PLEIN'],
            'description' => ['nullable', 'string', 'max:1000'],
        ]);

        $pointCollecte->update($validated);

        return redirect()
            ->route('collecte.points.show', $pointCollecte)
            ->with('success', 'Point de collecte mis à jour.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(PointCollecte $pointCollecte): RedirectResponse
    {
        $pointCollecte->delete();

        return redirect()
            ->route('collecte.points.index')
            ->with('success', 'Point de collecte supprimé.');
    }
}
