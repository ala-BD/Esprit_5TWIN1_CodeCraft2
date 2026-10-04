<?php

namespace App\Http\Controllers\Upcycling;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Upcycling\Concerns\AccesUpcycling;
use App\Models\Atelier;
use App\Models\ProjetUpcycling;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AtelierController extends Controller
{
    use AccesUpcycling;

    /*
    |------------------------------------------------------------------
    | GET /upcycling/ateliers — Catalogue des ateliers (avec filtres)
    |------------------------------------------------------------------
    */
    public function index(Request $request): View
    {
        $ateliers = Atelier::where('actif', true)
            ->when($request->filled('specialite'), fn ($q) => $q->where('specialite', $request->specialite))
            ->when($request->filled('q'), fn ($q) => $q->where(function ($sub) use ($request) {
                $sub->where('nom', 'like', '%' . $request->q . '%')
                    ->orWhere('localisation', 'like', '%' . $request->q . '%');
            }))
            ->withCount(['projetUpcyclings as projets_termines' => fn ($q) => $q->where('statut', ProjetUpcycling::STATUT_TERMINE)])
            ->orderByDesc('note_moyenne')
            ->paginate(9)
            ->withQueryString();

        return view('upcycling.ateliers.index', [
            'ateliers'     => $ateliers,
            'monAtelier'   => $this->atelierConnecte(),
        ]);
    }

    /*
    |------------------------------------------------------------------
    | GET /upcycling/ateliers/{atelier} — Portfolio d'un atelier
    |------------------------------------------------------------------
    */
    public function show(Atelier $atelier): View
    {
        $realisations = $atelier->projetUpcyclings()
            ->where('statut', ProjetUpcycling::STATUT_TERMINE)
            ->with('client')
            ->latest('date_fin')
            ->take(6)
            ->get();

        return view('upcycling.ateliers.show', compact('atelier', 'realisations'));
    }

    /*
    |------------------------------------------------------------------
    | GET /upcycling/ateliers/create — Créer son profil atelier
    |------------------------------------------------------------------
    */
    public function create(): View|RedirectResponse
    {
        abort_unless($this->estAtelier(), 403, 'Réservé aux comptes Atelier.');

        if ($atelier = $this->atelierConnecte()) {
            return redirect()->route('upcycling.ateliers.edit', $atelier);
        }

        return view('upcycling.ateliers.form', ['atelier' => new Atelier()]);
    }

    /*
    |------------------------------------------------------------------
    | POST /upcycling/ateliers — Enregistrer son profil atelier
    |------------------------------------------------------------------
    */
    public function store(Request $request): RedirectResponse
    {
        abort_unless($this->estAtelier(), 403, 'Réservé aux comptes Atelier.');
        abort_if($this->atelierConnecte() !== null, 422, 'Vous avez déjà un profil atelier.');

        $atelier = Atelier::create($this->valider($request) + [
            'user_id' => Auth::id(),
            'actif'   => true,
        ]);

        return redirect()
            ->route('upcycling.ateliers.show', $atelier)
            ->with('success', 'Profil atelier créé. Vous apparaissez maintenant dans le matching.');
    }

    /*
    |------------------------------------------------------------------
    | GET /upcycling/ateliers/{atelier}/edit — Modifier son portfolio
    |------------------------------------------------------------------
    */
    public function edit(Atelier $atelier): View
    {
        abort_if($atelier->user_id !== Auth::id(), 403);

        return view('upcycling.ateliers.form', compact('atelier'));
    }

    /*
    |------------------------------------------------------------------
    | PUT /upcycling/ateliers/{atelier}
    |------------------------------------------------------------------
    */
    public function update(Request $request, Atelier $atelier): RedirectResponse
    {
        abort_if($atelier->user_id !== Auth::id(), 403);

        $atelier->update($this->valider($request) + [
            'actif' => $request->boolean('actif'),
        ]);

        return redirect()
            ->route('upcycling.ateliers.show', $atelier)
            ->with('success', 'Profil atelier mis à jour.');
    }

    /*
    |------------------------------------------------------------------
    | DELETE /upcycling/ateliers/{atelier}
    |------------------------------------------------------------------
    */
    public function destroy(Atelier $atelier): RedirectResponse
    {
        abort_if($atelier->user_id !== Auth::id(), 403);

        $enCours = $atelier->projetUpcyclings()->whereIn('statut', ProjetUpcycling::STATUTS_EN_COURS)->exists();
        if ($enCours) {
            return back()->with('error', 'Impossible de supprimer un atelier qui a des projets en cours.');
        }

        $atelier->delete();

        return redirect()
            ->route('upcycling.dashboard')
            ->with('success', 'Profil atelier supprimé.');
    }

    /*
    |------------------------------------------------------------------
    | Validation commune store / update
    |------------------------------------------------------------------
    */
    private function valider(Request $request): array
    {
        return $request->validate([
            'nom'           => ['required', 'string', 'max:100'],
            'specialite'    => ['required', Rule::in(array_keys(Atelier::SPECIALITES))],
            'description'   => ['nullable', 'string', 'max:1000'],
            'portfolio_url' => ['nullable', 'url', 'max:255'],
            'tarif_horaire' => ['required', 'numeric', 'min:1', 'max:500'],
            'localisation'  => ['required', 'string', 'max:150'],
        ], [
            'nom.required'           => "Le nom de l'atelier est obligatoire.",
            'specialite.required'    => 'La spécialité est obligatoire.',
            'specialite.in'          => 'Spécialité invalide.',
            'portfolio_url.url'      => "Le lien du portfolio doit être une URL valide (https://...).",
            'tarif_horaire.required' => 'Le tarif horaire est obligatoire.',
            'tarif_horaire.numeric'  => 'Le tarif horaire doit être un nombre.',
            'tarif_horaire.min'      => 'Le tarif horaire minimum est 1 DT.',
            'tarif_horaire.max'      => 'Le tarif horaire maximum est 500 DT.',
            'localisation.required'  => 'La localisation est obligatoire.',
        ]);
    }
}
