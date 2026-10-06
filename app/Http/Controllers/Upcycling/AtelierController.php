<?php

namespace App\Http\Controllers\Upcycling;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Upcycling\Concerns\AccesUpcycling;
use App\Http\Requests\Upcycling\AtelierRequest;
use App\Models\Atelier;
use App\Models\ProjetUpcycling;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
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
            ->with(['projetUpcyclings' => fn ($q) => $q->where('statut', ProjetUpcycling::STATUT_TERMINE)->latest('date_fin')])
            ->orderByDesc('note_moyenne')
            ->paginate(9)
            ->withQueryString();

        return view('upcycling.ateliers.index', [
            'ateliers'   => $ateliers,
            'monAtelier' => $this->atelierConnecte(),
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
    public function store(AtelierRequest $request): RedirectResponse
    {
        abort_unless($this->estAtelier(), 403, 'Réservé aux comptes Atelier.');
        abort_if($this->atelierConnecte() !== null, 422, 'Vous avez déjà un profil atelier.');

        $atelier = Atelier::create($this->donnees($request) + [
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
    public function update(AtelierRequest $request, Atelier $atelier): RedirectResponse
    {
        abort_if($atelier->user_id !== Auth::id(), 403);

        $atelier->update($this->donnees($request, $atelier) + [
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

        ProjetUpcycling::supprimerPhoto($atelier->photo);
        $atelier->delete();

        return redirect()
            ->route('upcycling.dashboard')
            ->with('success', 'Profil atelier supprimé.');
    }

    /*
    |------------------------------------------------------------------
    | Données validées + photo de couverture
    |------------------------------------------------------------------
    */
    private function donnees(AtelierRequest $request, ?Atelier $atelier = null): array
    {
        $data = $request->safe()->except('photo');

        if ($request->hasFile('photo')) {
            ProjetUpcycling::supprimerPhoto($atelier?->photo);
            $data['photo'] = $request->file('photo')->store('upcycling/ateliers', 'public');
        }

        return $data;
    }
}
