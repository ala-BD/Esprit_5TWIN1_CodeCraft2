<?php

namespace App\Http\Controllers\Recyclage;

use App\Http\Controllers\Controller;
use App\Models\LotHistorique;
use App\Models\LotTextile;
use App\Models\Recycleur;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Illuminate\Support\Facades\Auth;

class LotTextileController extends Controller
{
    /*
    |------------------------------------------------------------------
    | GET /recyclage — Dashboard recycleur
    |------------------------------------------------------------------
    */
    public function dashboard(): View
    {
        $recycleur = Recycleur::where('user_id', Auth::id())->first();

        if (!$recycleur) {
            return view('recyclage.dashboard', [
                'recycleur' => null,
                'stats'     => [],
                'lots'      => collect(),
            ]);
        }

        $base = fn() => LotTextile::where('recycleur_id', $recycleur->id);

        $stats = [
            'total'         => ($base)()->count(),
            'en_attente'    => ($base)()->where('statut', 'EN_ATTENTE')->count(),
            'en_traitement' => ($base)()->where('statut', 'EN_TRAITEMENT')->count(),
            'certifies'     => ($base)()->where('statut', 'CERTIFIE')->count(),
            'total_kg'      => ($base)()->sum('poids_kg'),
        ];

        $lots = ($base)()->latest()->take(5)->get();

        // ----------------------------------------------------------------
        // Calcul des alertes métier
        // ----------------------------------------------------------------
        $alertes = collect();

        // Alerte 1 : lots EN_ATTENTE depuis plus de 7 jours
        $lotsEnAttenteLongs = ($base)()
            ->where('statut', 'EN_ATTENTE')
            ->where('created_at', '<=', now()->subDays(7))
            ->count();
        if ($lotsEnAttenteLongs > 0) {
            $alertes->push([
                'niveau'  => 'danger',
                'icone'   => 'fa-clock',
                'titre'   => 'Lots en attente trop longtemps',
                'message' => "{$lotsEnAttenteLongs} lot(s) sont en attente depuis plus de 7 jours sans traitement.",
                'lien'    => route('recyclage.lots.index', ['statut' => 'EN_ATTENTE', 'sort' => 'created_at', 'direction' => 'asc']),
                'lien_label' => 'Voir ces lots',
            ]);
        }

        // Alerte 2 : capacité de traitement dépassée à 80%
        $pctCapacite = $recycleur->capacite_kg > 0
            ? round(($stats['total_kg'] / $recycleur->capacite_kg) * 100)
            : 0;
        if ($pctCapacite >= 80) {
            $alertes->push([
                'niveau'  => $pctCapacite >= 95 ? 'danger' : 'warning',
                'icone'   => 'fa-warehouse',
                'titre'   => 'Capacité presque saturée',
                'message' => "Capacité utilisée à {$pctCapacite}% ({$stats['total_kg']} kg / {$recycleur->capacite_kg} kg). Pensez à libérer de l'espace.",
                'lien'    => route('recyclage.statistiques'),
                'lien_label' => 'Voir statistiques',
            ]);
        }

        // Alerte 3 : lots TRAITE sans passeport depuis plus de 3 jours
        $lotsTraitesSansPasseport = ($base)()
            ->where('statut', 'TRAITE')
            ->whereDoesntHave('passeportNumerique')
            ->where('updated_at', '<=', now()->subDays(3))
            ->count();
        if ($lotsTraitesSansPasseport > 0) {
            $alertes->push([
                'niveau'  => 'warning',
                'icone'   => 'fa-qrcode',
                'titre'   => 'Passeports manquants',
                'message' => "{$lotsTraitesSansPasseport} lot(s) traité(s) depuis plus de 3 jours n'ont pas encore de passeport numérique.",
                'lien'    => route('recyclage.lots.index', ['statut' => 'TRAITE']),
                'lien_label' => 'Générer les passeports',
            ]);
        }

        // Alerte 4 : aucun lot enregistré ce mois-ci (si le recycleur est actif)
        $lotsCresMois = ($base)()
            ->whereYear('created_at', now()->year)
            ->whereMonth('created_at', now()->month)
            ->count();
        if ($lotsCresMois === 0 && $stats['total'] > 0) {
            $alertes->push([
                'niveau'  => 'info',
                'icone'   => 'fa-calendar-alt',
                'titre'   => 'Aucun lot ce mois-ci',
                'message' => "Aucun nouveau lot n'a été enregistré ce mois-ci. Pensez à enregistrer vos lots.",
                'lien'    => route('recyclage.lots.create'),
                'lien_label' => 'Enregistrer un lot',
            ]);
        }

        return view('recyclage.dashboard', compact('recycleur', 'stats', 'lots', 'alertes'));
    }

    /*
    |------------------------------------------------------------------
    | GET /recyclage/lots — Liste des lots avec filtres avancés
    |------------------------------------------------------------------
    */
    public function index(Request $request): View
    {
        $recycleur = Recycleur::where('user_id', Auth::id())->firstOrFail();

        $query = LotTextile::where('recycleur_id', $recycleur->id)
            ->with(['etapeTraitements', 'passeportNumerique']);

        // --- Filtre recherche textuelle (référence ou composition) ---
        if ($request->filled('q')) {
            $q = '%' . $request->q . '%';
            $query->where(fn($sub) =>
                $sub->where('reference', 'like', $q)
                    ->orWhere('composition', 'like', $q)
                    ->orWhere('origine', 'like', $q)
            );
        }

        // --- Filtre statut ---
        if ($request->filled('statut') && in_array($request->statut, ['EN_ATTENTE', 'EN_TRAITEMENT', 'TRAITE', 'CERTIFIE'])) {
            $query->where('statut', $request->statut);
        }

        // --- Filtre filière IA ---
        if ($request->filled('filiere') && in_array($request->filiere, ['REVENTE', 'UPCYCLING', 'RECYCLAGE_FIBRE', 'RECYCLAGE_ENERGIE'])) {
            $query->where('filiere_recommandee_ia', $request->filiere);
        }

        // --- Filtre période ---
        if ($request->filled('date_debut')) {
            $query->whereDate('created_at', '>=', $request->date_debut);
        }
        if ($request->filled('date_fin')) {
            $query->whereDate('created_at', '<=', $request->date_fin);
        }

        // --- Filtre poids ---
        if ($request->filled('poids_min')) {
            $query->where('poids_kg', '>=', (float) $request->poids_min);
        }
        if ($request->filled('poids_max')) {
            $query->where('poids_kg', '<=', (float) $request->poids_max);
        }

        // --- Tri ---
        $sortField = in_array($request->sort, ['reference', 'poids_kg', 'created_at', 'statut'])
            ? $request->sort : 'created_at';
        $sortDir = $request->direction === 'asc' ? 'asc' : 'desc';
        $query->orderBy($sortField, $sortDir);

        $lots = $query->paginate(10)->withQueryString();

        // Stats globales (sur l'ensemble, pas les filtrés)
        $base = LotTextile::where('recycleur_id', $recycleur->id);
        $stats = [
            'total'         => (clone $base)->count(),
            'en_attente'    => (clone $base)->where('statut', 'EN_ATTENTE')->count(),
            'en_traitement' => (clone $base)->where('statut', 'EN_TRAITEMENT')->count(),
            'certifies'     => (clone $base)->where('statut', 'CERTIFIE')->count(),
            'total_kg'      => (clone $base)->sum('poids_kg'),
        ];

        // Nombre de résultats filtrés
        $filtresActifs = collect(['q', 'statut', 'filiere', 'date_debut', 'date_fin', 'poids_min', 'poids_max'])
            ->filter(fn($k) => $request->filled($k))->count();

        return view('recyclage.lots.index', compact('lots', 'recycleur', 'stats', 'filtresActifs'));
    }

    /*
    |------------------------------------------------------------------
    | GET /recyclage/lots/create — Formulaire création
    |------------------------------------------------------------------
    */
    public function create(): View
    {
        return view('recyclage.lots.create');
    }

    /*
    |------------------------------------------------------------------
    | POST /recyclage/lots — Enregistrer un lot
    |------------------------------------------------------------------
    */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'reference'   => ['required', 'string', 'max:50', 'unique:lot_textiles,reference'],
            'poids_kg'    => ['required', 'numeric', 'min:0.1'],
            'composition' => ['required', 'string', 'max:255'],
            'origine'     => ['required', 'string', 'max:255'],
        ], [
            'reference.required'   => 'La référence du lot est obligatoire.',
            'reference.unique'     => 'Cette référence existe déjà.',
            'poids_kg.required'    => 'Le poids est obligatoire.',
            'poids_kg.numeric'     => 'Le poids doit être un nombre.',
            'poids_kg.min'         => 'Le poids minimum est 0.1 kg.',
            'composition.required' => 'La composition est obligatoire.',
            'origine.required'     => "L'origine est obligatoire.",
        ]);

        $recycleur = Recycleur::where('user_id', Auth::id())->firstOrFail();

        // Recommandation IA simulée
        $filiereIA = LotTextile::recommanderFiliereIA(
            $request->composition,
            (float) $request->poids_kg
        );

        $lot = LotTextile::create([
            'recycleur_id'          => $recycleur->id,
            'reference'             => strtoupper($request->reference),
            'poids_kg'              => $request->poids_kg,
            'composition'           => $request->composition,
            'origine'               => $request->origine,
            'filiere_recommandee_ia'=> $filiereIA,
            'statut'                => 'EN_ATTENTE',
        ]);

        // Historique : création
        LotHistorique::enregistrer(
            $lot,
            LotHistorique::CREATION,
            "Lot {$lot->reference} créé — {$lot->poids_kg} kg, filière IA : {$filiereIA}",
        );

        return redirect()
            ->route('recyclage.lots.show', $lot)
            ->with('success', "Lot {$lot->reference} enregistré. Filière recommandée : {$filiereIA}");
    }

    /*
    |------------------------------------------------------------------
    | GET /recyclage/lots/{lot} — Détail d'un lot
    |------------------------------------------------------------------
    */
    public function show(LotTextile $lot): View
    {
        // S'assurer que le recycleur connecté est propriétaire
        $recycleur = Recycleur::where('user_id', Auth::id())->firstOrFail();
        abort_if($lot->recycleur_id !== $recycleur->id, 403, 'Accès non autorisé.');

        $lot->load(['etapeTraitements', 'passeportNumerique', 'donVetement', 'historiques.user']);

        // Étapes déjà enregistrées
        $etapesEnregistrees = $lot->etapeTraitements->pluck('type')->toArray();

        // Prochaine étape disponible
        $toutesEtapes = array_keys(\App\Models\EtapeTraitement::ORDRE);
        $prochaineEtape = collect($toutesEtapes)
            ->first(fn($e) => !in_array($e, $etapesEnregistrees));

        // Dons disponibles pour rattachement (EN_TRI, pas encore liés à un lot)
        $donsDisponibles = \App\Models\DonVetement::where('statut', 'EN_TRI')
            ->whereDoesntHave('lotTextile')
            ->with('pointCollecte')
            ->latest()
            ->get();

        // Historique du lot
        $historiques = $lot->historiques;

        return view('recyclage.lots.show', compact('lot', 'etapesEnregistrees', 'prochaineEtape', 'donsDisponibles', 'historiques'));
    }

    /*
    |------------------------------------------------------------------
    | GET /recyclage/lots/{lot}/edit — Formulaire édition
    |------------------------------------------------------------------
    */
    public function edit(LotTextile $lot): View
    {
        $recycleur = Recycleur::where('user_id', Auth::id())->firstOrFail();
        abort_if($lot->recycleur_id !== $recycleur->id, 403);

        return view('recyclage.lots.edit', compact('lot'));
    }

    /*
    |------------------------------------------------------------------
    | PUT /recyclage/lots/{lot} — Mettre à jour un lot
    |------------------------------------------------------------------
    */
    public function update(Request $request, LotTextile $lot): RedirectResponse
    {
        $recycleur = Recycleur::where('user_id', Auth::id())->firstOrFail();
        abort_if($lot->recycleur_id !== $recycleur->id, 403);

        $request->validate([
            'composition' => ['required', 'string', 'max:255'],
            'origine'     => ['required', 'string', 'max:255'],
            'statut'      => ['required', 'in:EN_ATTENTE,EN_TRAITEMENT,TRAITE,CERTIFIE'],
        ]);

        // Capturer les changements avant update
        $ancienStatut = $lot->statut;
        $ancienneComp = $lot->composition;

        $lot->update($request->only(['composition', 'origine', 'statut']));

        // Historique statut
        if ($ancienStatut !== $lot->statut) {
            LotHistorique::enregistrer(
                $lot,
                LotHistorique::STATUT_CHANGE,
                "Statut modifié : {$ancienStatut} → {$lot->statut}",
                'statut', $ancienStatut, $lot->statut
            );
        }
        // Historique composition
        if ($ancienneComp !== $lot->composition) {
            LotHistorique::enregistrer(
                $lot,
                LotHistorique::MODIFICATION,
                "Composition modifiée",
                'composition', $ancienneComp, $lot->composition
            );
        }

        return redirect()
            ->route('recyclage.lots.show', $lot)
            ->with('success', 'Lot mis à jour avec succès.');
    }

    /*
    |------------------------------------------------------------------
    | POST /recyclage/lots/{lot}/rattacher-don — Rattacher un don au lot
    |------------------------------------------------------------------
    */
    public function rattacherDon(Request $request, LotTextile $lot): RedirectResponse
    {
        $recycleur = Recycleur::where('user_id', Auth::id())->firstOrFail();
        abort_if($lot->recycleur_id !== $recycleur->id, 403);
        abort_if($lot->statut === 'CERTIFIE', 422, 'Impossible de modifier un lot certifié.');

        $request->validate([
            'don_vetement_id' => ['required', 'integer', 'exists:don_vetements,id'],
        ], [
            'don_vetement_id.required' => 'Veuillez sélectionner un don.',
            'don_vetement_id.exists'   => 'Ce don est introuvable.',
        ]);

        $don = \App\Models\DonVetement::findOrFail($request->don_vetement_id);

        // Vérifier que le don est bien EN_TRI et pas déjà rattaché
        abort_if($don->statut !== 'EN_TRI', 422, 'Ce don doit être en statut EN_TRI.');
        abort_if($don->lotTextile()->exists(), 422, 'Ce don est déjà rattaché à un lot.');

        // Rattacher le don au lot
        $don->update(['statut' => 'RECYCLE']);
        $lot->update(['don_vetement_id' => $don->id]);

        // Historique
        LotHistorique::enregistrer(
            $lot,
            LotHistorique::DON_RATTACHE,
            "Don n°{$don->id} ({$don->type} — {$don->matiere}) rattaché au lot. Statut don : RECYCLÉ.",
            'don_vetement_id', null, (string) $don->id
        );

        return redirect()
            ->route('recyclage.lots.show', $lot)
            ->with('success', "Don n°{$don->id} ({$don->type} — {$don->matiere}) rattaché au lot {$lot->reference}. Statut mis à jour : RECYCLÉ.");
    }

    /*
    |------------------------------------------------------------------
    | DELETE /recyclage/lots/{lot}/detacher-don — Détacher le don du lot
    |------------------------------------------------------------------
    */
    public function detacherDon(LotTextile $lot): RedirectResponse
    {
        $recycleur = Recycleur::where('user_id', Auth::id())->firstOrFail();
        abort_if($lot->recycleur_id !== $recycleur->id, 403);
        abort_if($lot->statut === 'CERTIFIE', 422, 'Impossible de modifier un lot certifié.');
        abort_if(!$lot->don_vetement_id, 422, 'Aucun don rattaché à ce lot.');

        $don = $lot->donVetement;

        // Remettre le don en EN_TRI
        $don->update(['statut' => 'EN_TRI']);
        $lot->update(['don_vetement_id' => null]);

        // Historique
        LotHistorique::enregistrer(
            $lot,
            LotHistorique::DON_DETACHE,
            "Don n°{$don->id} ({$don->type}) détaché. Statut don remis à EN_TRI.",
            'don_vetement_id', (string) $don->id, null
        );

        return redirect()
            ->route('recyclage.lots.show', $lot)
            ->with('success', "Don n°{$don->id} détaché du lot {$lot->reference}. Statut remis à EN_TRI.");
    }

    /*
    |------------------------------------------------------------------
    | GET /recyclage/dons-disponibles — Liste des dons disponibles (JSON)
    |------------------------------------------------------------------
    */
    public function donsDisponibles(): \Illuminate\Http\JsonResponse
    {
        $dons = \App\Models\DonVetement::where('statut', 'EN_TRI')
            ->whereDoesntHave('lotTextile')
            ->with('pointCollecte')
            ->latest()
            ->get()
            ->map(fn($d) => [
                'id'     => $d->id,
                'label'  => "Don n°{$d->id} — {$d->type}, {$d->matiere} ({$d->etat})",
                'point'  => $d->pointCollecte?->nom ?? 'N/A',
                'date'   => $d->date_depot?->format('d/m/Y'),
            ]);

        return response()->json($dons);
    }

    /*
    |------------------------------------------------------------------
    | DELETE /recyclage/lots/{lot} — Supprimer un lot
    |------------------------------------------------------------------
    */
    public function destroy(LotTextile $lot): RedirectResponse
    {
        $recycleur = Recycleur::where('user_id', Auth::id())->firstOrFail();
        abort_if($lot->recycleur_id !== $recycleur->id, 403);
        abort_if($lot->statut === 'CERTIFIE', 422, 'Impossible de supprimer un lot certifié.');

        $ref = $lot->reference;
        $lot->delete();

        return redirect()
            ->route('recyclage.lots.index')
            ->with('success', "Lot {$ref} supprimé.");
    }
}
