<?php

namespace App\Http\Controllers\Upcycling;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Upcycling\Concerns\AccesUpcycling;
use App\Http\Requests\Upcycling\ProjetUpcyclingRequest;
use App\Models\Article;
use App\Models\Atelier;
use App\Models\Devis;
use App\Models\DonVetement;
use App\Models\ProjetUpcycling;
use App\Notifications\UpcyclingNotification;
use App\Services\Upcycling\ControleQualiteService;
use App\Services\Upcycling\IdeeUpcyclingService;
use App\Services\Upcycling\MatchingAtelierService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Throwable;

class ProjetUpcyclingController extends Controller
{
    use AccesUpcycling;

    public function __construct(
        private IdeeUpcyclingService $ideeService,
        private MatchingAtelierService $matchingService,
        private ControleQualiteService $controleQualite,
    ) {}

    /*
    |------------------------------------------------------------------
    | GET /upcycling — Dashboard (client, atelier ou admin)
    |------------------------------------------------------------------
    */
    public function dashboard(): View
    {
        if ($this->estAtelier()) {
            $atelier = $this->atelierConnecte();
            if (!$atelier) {
                return view('upcycling.dashboard', ['atelier' => null, 'stats' => [], 'projets' => collect(), 'impact' => null]);
            }

            $base = ProjetUpcycling::where('atelier_id', $atelier->id);
            $stats = [
                'a_chiffrer'  => (clone $base)->where('statut', ProjetUpcycling::STATUT_ATELIER_CHOISI)
                                    ->whereDoesntHave('devis', fn ($q) => $q->where('statut', Devis::STATUT_EN_ATTENTE))->count(),
                'devis_attente' => Devis::whereIn('projet_upcycling_id', (clone $base)->select('id'))
                                    ->where('statut', Devis::STATUT_EN_ATTENTE)->count(),
                'en_cours'    => (clone $base)->whereIn('statut', ProjetUpcycling::STATUTS_EN_COURS)->count(),
                'termines'    => (clone $base)->where('statut', ProjetUpcycling::STATUT_TERMINE)->count(),
                'chiffre_affaires' => Devis::whereIn('projet_upcycling_id', (clone $base)->where('statut', ProjetUpcycling::STATUT_TERMINE)->select('id'))
                                    ->where('statut', Devis::STATUT_ACCEPTE)->sum('montant'),
            ];
        } else {
            $atelier = null;
            $base = Auth::user()->isAdmin()
                ? ProjetUpcycling::query()
                : ProjetUpcycling::where('client_id', Auth::id());

            $stats = [
                'total'      => (clone $base)->count(),
                'en_attente' => (clone $base)->whereIn('statut', [ProjetUpcycling::STATUT_DEMANDE, ProjetUpcycling::STATUT_ATELIER_CHOISI])->count(),
                'en_cours'   => (clone $base)->whereIn('statut', ProjetUpcycling::STATUTS_EN_COURS)->count(),
                'termines'   => (clone $base)->where('statut', ProjetUpcycling::STATUT_TERMINE)->count(),
            ];
        }

        // Impact écologique des projets terminés
        $termines = (clone $base)->where('statut', ProjetUpcycling::STATUT_TERMINE);
        $impact = [
            'co2' => (clone $termines)->sum('co2_evite_kg'),
            'eau' => (clone $termines)->sum('eau_economisee_l'),
            'nb'  => (clone $termines)->count(),
        ];

        $projets = (clone $base)->with(['atelier', 'client'])->latest('updated_at')->take(6)->get();
        $graphiques = $this->graphiques((clone $base)->with('devis')->get(), $atelier !== null);

        return view('upcycling.dashboard', compact('atelier', 'stats', 'projets', 'impact', 'graphiques'));
    }

    /**
     * Données des graphiques du dashboard (calculées en PHP : indépendant du SGBD).
     *  - atelier : chiffre d'affaires des projets terminés sur 6 mois
     *  - client  : projets créés sur 6 mois
     *  - tous    : répartition par catégorie de produit
     */
    private function graphiques($projets, bool $vueAtelier): array
    {
        $mois = collect(range(5, 0))->map(fn ($i) => now()->startOfMonth()->subMonths($i));

        $valeurs = $mois->map(function ($debut) use ($projets, $vueAtelier) {
            $fin = $debut->copy()->endOfMonth();

            return $vueAtelier
                ? $projets->filter(fn ($p) => $p->statut === ProjetUpcycling::STATUT_TERMINE && $p->date_fin?->between($debut, $fin))
                          ->sum(fn ($p) => $p->devis_accepte?->montant ?? 0)
                : $projets->filter(fn ($p) => $p->created_at->between($debut, $fin))->count();
        });

        $categories = $projets->whereNotNull('categorie_produit')
            ->countBy('categorie_produit')
            ->mapWithKeys(fn ($nb, $code) => [Atelier::SPECIALITES[$code] ?? $code => $nb]);

        return [
            'titre'      => $vueAtelier ? "Chiffre d'affaires (DT)" : 'Projets créés',
            'mois'       => $mois->map(fn ($d) => ucfirst($d->translatedFormat('M Y')))->all(),
            'valeurs'    => $valeurs->values()->all(),
            'categories' => $categories->all(),
        ];
    }

    /*
    |------------------------------------------------------------------
    | GET /upcycling/projets — Liste filtrée selon le rôle
    |------------------------------------------------------------------
    */
    public function index(Request $request): View
    {
        $query = ProjetUpcycling::with(['atelier', 'client']);

        if ($this->estAtelier()) {
            $query->where('atelier_id', $this->atelierConnecte()?->id ?? 0);
        } elseif (!Auth::user()->isAdmin()) {
            $query->where('client_id', Auth::id());
        }

        $projets = $query
            ->when($request->filled('statut'), fn ($q) => $q->where('statut', $request->statut))
            ->when($request->filled('q'), fn ($q) => $q->where(function ($sub) use ($request) {
                $sub->where('type_vetement', 'like', '%' . $request->q . '%')
                    ->orWhere('produit_final', 'like', '%' . $request->q . '%');
            }))
            ->latest()
            ->paginate(9)
            ->withQueryString();

        return view('upcycling.projets.index', compact('projets'));
    }

    /*
    |------------------------------------------------------------------
    | GET /upcycling/projets/create — Nouvelle demande d'upcycling
    |------------------------------------------------------------------
    */
    public function create(): View
    {
        abort_unless($this->estClient(), 403, 'Réservé aux clients.');

        return view('upcycling.projets.create', [
            'dons'       => ProjetUpcyclingRequest::donsDisponibles(),
            'iaActivee'  => $this->ideeService->estConfigure(),
        ]);
    }

    /*
    |------------------------------------------------------------------
    | POST /upcycling/analyse-photo — L'IA reconnaît le vêtement (AJAX)
    |------------------------------------------------------------------
    */
    public function analyserPhoto(Request $request): JsonResponse
    {
        abort_unless($this->estClient(), 403);

        $request->validate([
            'photo' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ], [
            'photo.required' => 'Ajoutez une photo du vêtement.',
            'photo.image'    => 'Le fichier doit être une image.',
            'photo.mimes'    => 'Formats acceptés : JPG, PNG ou WEBP.',
            'photo.max'      => 'La photo ne doit pas dépasser 5 Mo.',
        ]);

        if (!$this->ideeService->estConfigure()) {
            return response()->json(['message' => "L'analyse IA n'est pas configurée (clé GEMINI_API_KEY manquante)."], 503);
        }

        try {
            $analyse = $this->ideeService->analyserPhoto($request->file('photo')->getRealPath());
        } catch (Throwable $e) {
            Log::warning('Upcycling IA : analyse photo échouée.', ['erreur' => $e->getMessage()]);
            return response()->json(['message' => "L'IA n'a pas pu analyser la photo. Réessayez ou remplissez le formulaire."], 502);
        }

        if (!$analyse['est_vetement']) {
            return response()->json(['message' => "L'IA ne reconnaît pas de vêtement sur cette photo."], 422);
        }

        return response()->json(['analyse' => $analyse]);
    }

    /*
    |------------------------------------------------------------------
    | POST /upcycling/projets — Enregistrer + générer les idées IA
    |------------------------------------------------------------------
    */
    public function store(ProjetUpcyclingRequest $request): RedirectResponse
    {
        abort_unless($this->estClient(), 403, 'Réservé aux clients.');

        $data = $request->safe()->except('photo');
        if ($request->hasFile('photo')) {
            $data['photo'] = $request->file('photo')->store(ProjetUpcycling::DOSSIER_PHOTOS, 'public');
        }

        $ia = $this->ideeService->generer($data, $this->cheminPhoto($data['photo'] ?? null));

        $projet = DB::transaction(function () use ($data, $ia) {
            $projet = ProjetUpcycling::create($data + $this->ideeService->impact($data['type_vetement']) + [
                'client_id'       => Auth::id(),
                'idee_generee_ia' => $ia['idees'],
                'source_ia'       => $ia['source'],
                'analyse_ia'      => ['defauts' => $ia['defauts']],
                'statut'          => ProjetUpcycling::STATUT_DEMANDE,
            ]);

            if ($projet->don_vetement_id) {
                DonVetement::whereKey($projet->don_vetement_id)->update(['statut' => 'UPCYCLING']);
            }

            return $projet;
        });

        return redirect()
            ->route('upcycling.projets.show', $projet)
            ->with('success', "Demande enregistrée. L'IA vous propose " . count($ia['idees']) . ' idées : choisissez celle qui vous plaît.');
    }

    /*
    |------------------------------------------------------------------
    | GET /upcycling/projets/{projet} — Détail + suivi par étapes
    |------------------------------------------------------------------
    */
    public function show(ProjetUpcycling $projet): View
    {
        $this->autoriserLecture($projet);

        $projet->load(['atelier.user', 'client', 'donVetement', 'devis', 'messages.user', 'article']);

        return view('upcycling.projets.show', [
            'projet'           => $projet,
            'estProprietaire'  => $this->estProprietaire($projet),
            'estAtelierProjet' => $this->estAtelierDuProjet($projet),
            'prixSuggere'      => $this->prixSuggere($projet),
        ]);
    }

    /*
    |------------------------------------------------------------------
    | GET /upcycling/projets/{projet}/edit
    |------------------------------------------------------------------
    */
    public function edit(ProjetUpcycling $projet): View|RedirectResponse
    {
        $this->autoriserClient($projet);

        if (!$projet->estModifiable()) {
            return redirect()->route('upcycling.projets.show', $projet)
                ->with('error', 'Ce projet ne peut plus être modifié : les travaux ont commencé.');
        }

        return view('upcycling.projets.edit', compact('projet'));
    }

    /*
    |------------------------------------------------------------------
    | PUT /upcycling/projets/{projet}
    |------------------------------------------------------------------
    */
    public function update(ProjetUpcyclingRequest $request, ProjetUpcycling $projet): RedirectResponse
    {
        $this->autoriserClient($projet);
        abort_unless($projet->estModifiable(), 422, 'Ce projet ne peut plus être modifié.');

        $data = $request->safe()->except('photo');
        $nouvellePhoto = $request->hasFile('photo');

        if ($nouvellePhoto) {
            ProjetUpcycling::supprimerPhoto($projet->photo);
            $data['photo'] = $request->file('photo')->store(ProjetUpcycling::DOSSIER_PHOTOS, 'public');
        }

        // Le vêtement a changé : les idées précédentes ne sont plus pertinentes
        $vetementModifie = $nouvellePhoto
            || $data['type_vetement'] !== $projet->type_vetement
            || $data['matiere'] !== $projet->matiere
            || $data['etat'] !== $projet->etat;

        $message = 'Demande mise à jour.';
        if ($vetementModifie) {
            $ia = $this->ideeService->generer($data, $this->cheminPhoto($data['photo'] ?? $projet->photo));
            $data += $this->ideeService->impact($data['type_vetement']) + [
                'idee_generee_ia'   => $ia['idees'],
                'source_ia'         => $ia['source'],
                'analyse_ia'        => ['defauts' => $ia['defauts']],
                'produit_final'     => null,
                'categorie_produit' => null,
                'prix_estime_min'   => null,
                'prix_estime_max'   => null,
            ];
            $message .= " Le vêtement a changé : l'IA a généré de nouvelles idées.";
        }

        $projet->update($data);

        return redirect()->route('upcycling.projets.show', $projet)->with('success', $message);
    }

    /*
    |------------------------------------------------------------------
    | DELETE /upcycling/projets/{projet}
    |------------------------------------------------------------------
    */
    public function destroy(ProjetUpcycling $projet): RedirectResponse
    {
        $this->autoriserClient($projet);
        abort_unless(
            in_array($projet->statut, [ProjetUpcycling::STATUT_DEMANDE, ProjetUpcycling::STATUT_ANNULE], true),
            422,
            'Seule une demande sans atelier ou annulée peut être supprimée.'
        );

        $this->libererDon($projet);
        ProjetUpcycling::supprimerPhoto($projet->photo);
        ProjetUpcycling::supprimerPhoto($projet->photo_resultat);
        $projet->delete();

        return redirect()->route('upcycling.projets.index')->with('success', 'Demande supprimée.');
    }

    /*
    |------------------------------------------------------------------
    | POST /upcycling/projets/{projet}/idees — Relancer l'IA (+ consigne)
    |------------------------------------------------------------------
    */
    public function regenererIdees(Request $request, ProjetUpcycling $projet): RedirectResponse
    {
        $this->autoriserClient($projet);
        abort_unless($projet->estModifiable(), 422);

        $request->validate([
            'consigne' => ['nullable', 'string', 'max:200'],
        ], [
            'consigne.max' => 'La consigne ne doit pas dépasser 200 caractères.',
        ]);

        $ia = $this->ideeService->generer($projet->only(['type_vetement', 'matiere', 'etat', 'couleur', 'description', 'budget_max']),
            $this->cheminPhoto($projet->photo),
            $request->consigne
        );

        $projet->update([
            'idee_generee_ia' => $ia['idees'],
            'source_ia'       => $ia['source'],
            'analyse_ia'      => ['defauts' => $ia['defauts']],
        ]);

        return back()->with('success', $request->filled('consigne')
            ? "Nouvelles idées générées selon votre demande : « {$request->consigne} »."
            : "Nouvelles idées générées par l'IA.");
    }

    /*
    |------------------------------------------------------------------
    | PATCH /upcycling/projets/{projet}/idee — Retenir une idée
    |------------------------------------------------------------------
    */
    public function choisirIdee(Request $request, ProjetUpcycling $projet): RedirectResponse
    {
        $this->autoriserClient($projet);
        abort_unless($projet->estModifiable(), 422);

        $idees = $projet->idee_generee_ia ?? [];
        abort_if(count($idees) === 0, 422, 'Aucune idée à choisir.');

        $request->validate([
            'index' => ['required', 'integer', 'min:0', 'max:' . (count($idees) - 1)],
        ]);

        $idee = $idees[$request->integer('index')];
        $projet->update([
            'produit_final'     => $idee['titre'],
            'categorie_produit' => $idee['categorie'],
            'prix_estime_min'   => $idee['prix_min'] ?? null,
            'prix_estime_max'   => $idee['prix_max'] ?? null,
        ]);

        $suite = $projet->atelier_id ? '' : " Trouvez maintenant l'atelier idéal.";
        return redirect()
            ->route($projet->atelier_id ? 'upcycling.projets.show' : 'upcycling.projets.matching', $projet)
            ->with('success', "Idée « {$idee['titre']} » retenue." . $suite);
    }

    /*
    |------------------------------------------------------------------
    | GET /upcycling/projets/{projet}/matching — Ateliers recommandés
    |------------------------------------------------------------------
    */
    public function matching(ProjetUpcycling $projet): View|RedirectResponse
    {
        $this->autoriserClient($projet);

        if (!$projet->estModifiable()) {
            return redirect()->route('upcycling.projets.show', $projet);
        }
        if (!$projet->produit_final) {
            return redirect()->route('upcycling.projets.show', $projet)
                ->with('error', "Choisissez d'abord une idée de transformation.");
        }

        $classement = $this->matchingService->classer($projet);
        $methodeAffinite = $this->matchingService->methodeAffinite;

        return view('upcycling.projets.matching', compact('projet', 'classement', 'methodeAffinite'));
    }

    /*
    |------------------------------------------------------------------
    | PATCH /upcycling/projets/{projet}/atelier — Choisir l'atelier
    |------------------------------------------------------------------
    */
    public function choisirAtelier(Request $request, ProjetUpcycling $projet): RedirectResponse
    {
        $this->autoriserClient($projet);
        abort_unless($projet->estModifiable(), 422);

        $request->validate([
            'atelier_id' => ['required', Rule::exists('ateliers', 'id')->where('actif', true)],
        ], [
            'atelier_id.required' => 'Veuillez choisir un atelier.',
            'atelier_id.exists'   => 'Cet atelier est indisponible.',
        ]);

        DB::transaction(function () use ($projet, $request) {
            // Changement d'atelier : le devis en attente de l'ancien atelier tombe
            $projet->devis()->where('statut', Devis::STATUT_EN_ATTENTE)->update(['statut' => Devis::STATUT_REFUSE]);

            $projet->update([
                'atelier_id' => $request->integer('atelier_id'),
                'statut'     => ProjetUpcycling::STATUT_ATELIER_CHOISI,
            ]);
        });

        $atelier = Atelier::find($request->integer('atelier_id'));
        $atelier->user->notify(new UpcyclingNotification(
            $projet,
            "Nouvelle demande de devis : « {$projet->produit_final} » ({$projet->type_vetement}) de {$projet->client->full_name}.",
            'fa-file-invoice-dollar'
        ));

        return redirect()
            ->route('upcycling.projets.show', $projet)
            ->with('success', "Demande de devis envoyée à l'atelier {$atelier->nom}.");
    }

    /*
    |------------------------------------------------------------------
    | PATCH /upcycling/projets/{projet}/avancer — Étape suivante (atelier)
    |------------------------------------------------------------------
    */
    public function avancer(Request $request, ProjetUpcycling $projet): RedirectResponse
    {
        $this->autoriserAtelier($projet);

        $suivante = $projet->etape_suivante;
        abort_if($suivante === null, 422, 'Aucune étape suivante pour ce projet.');

        $request->validate([
            'photo_resultat' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ], [
            'photo_resultat.image' => 'Le fichier doit être une image.',
            'photo_resultat.mimes' => 'Formats acceptés : JPG, PNG ou WEBP.',
            'photo_resultat.max'   => 'La photo ne doit pas dépasser 5 Mo.',
        ]);

        $data = ['statut' => $suivante];
        if ($suivante === ProjetUpcycling::STATUT_TERMINE) {
            $data['date_fin'] = now();
            if ($request->hasFile('photo_resultat')) {
                ProjetUpcycling::supprimerPhoto($projet->photo_resultat);
                $data['photo_resultat'] = $request->file('photo_resultat')->store(ProjetUpcycling::DOSSIER_PHOTOS, 'public');
            }
        }

        $projet->update($data);
        $label = ProjetUpcycling::ETAPES[$suivante]['label'];
        $message = "Projet passé à l'étape « {$label} ».";

        // Fin des travaux : l'IA compare le produit fini au vêtement d'origine
        if ($suivante === ProjetUpcycling::STATUT_TERMINE && $projet->photo_resultat) {
            if ($controle = $this->controleQualite->verifier($projet)) {
                $projet->update(['controle_qualite_ia' => $controle]);
                $message .= " Contrôle qualité IA : {$controle['score']}/100.";
            }
        }

        $projet->client->notify(new UpcyclingNotification(
            $projet,
            $suivante === ProjetUpcycling::STATUT_TERMINE
                ? "Votre « {$projet->produit_final} » est terminé ! Découvrez le résultat et laissez un avis."
                : "Votre projet « {$projet->produit_final} » avance : étape « {$label} ».",
            $suivante === ProjetUpcycling::STATUT_TERMINE ? 'fa-gift' : ProjetUpcycling::ETAPES[$suivante]['icon']
        ));

        return back()->with('success', $message);
    }

    /*
    |------------------------------------------------------------------
    | POST /upcycling/projets/{projet}/controle-qualite — Relancer l'IA
    |------------------------------------------------------------------
    */
    public function controleQualite(ProjetUpcycling $projet): RedirectResponse
    {
        $this->autoriserAtelier($projet);
        abort_unless($projet->statut === ProjetUpcycling::STATUT_TERMINE && $projet->photo_resultat, 422,
            'Le contrôle qualité nécessite un projet terminé avec la photo du produit fini.');

        $controle = $this->controleQualite->verifier($projet);
        if (!$controle) {
            return back()->with('error', "Le contrôle qualité IA est indisponible pour le moment (clé Gemini absente ou service injoignable).");
        }

        $projet->update(['controle_qualite_ia' => $controle]);

        return back()->with('success', "Contrôle qualité IA effectué : {$controle['score']}/100.");
    }

    /*
    |------------------------------------------------------------------
    | POST /upcycling/projets/{projet}/publier — Revente sur la marketplace (M2)
    |------------------------------------------------------------------
    */
    public function publier(Request $request, ProjetUpcycling $projet): RedirectResponse
    {
        $this->autoriserClient($projet);
        abort_unless($projet->estPubliable(), 422, 'Ce produit ne peut pas (ou plus) être publié.');

        $request->validate([
            'prix'        => ['required', 'numeric', 'min:1', 'max:10000'],
            'description' => ['nullable', 'string', 'max:1000'],
        ], [
            'prix.required' => 'Indiquez un prix de vente.',
            'prix.numeric'  => 'Le prix doit être un nombre.',
            'prix.min'      => 'Le prix minimum est 1 DT.',
        ]);

        // Copie de la photo : l'article reste valable même si le projet est supprimé
        $source = $this->cheminPhoto($projet->photo_resultat);
        $image  = 'articles/upcycling-' . $projet->id . '-' . uniqid() . '.' . pathinfo($source, PATHINFO_EXTENSION);
        Storage::disk('public')->put($image, file_get_contents($source));

        $article = DB::transaction(function () use ($request, $projet, $image) {
            $article = Article::create([
                'user_id'         => $projet->client_id,
                'don_vetement_id' => $projet->don_vetement_id,
                'titre'           => $projet->produit_final,
                'description'     => $request->description ?: $this->descriptionArticle($projet),
                'prix'            => $request->prix,
                'prix_estime_ia'  => $this->prixSuggere($projet),
                'stock'           => 1,
                'categorie'       => 'Textile Upcyclé',
                'statut'          => Article::STATUT_DISPONIBLE,
                'images'          => [$image],
            ]);
            $projet->update(['article_id' => $article->id]);

            return $article;
        });

        return redirect()
            ->route('upcycling.projets.show', $projet)
            ->with('success', "« {$article->titre} » est en vente sur la marketplace à " . number_format($article->prix, 2, ',', ' ') . ' DT.');
    }

    /** Prix de revente suggéré : coût de la transformation + 30 % (arrondi à 5 DT) */
    public function prixSuggere(ProjetUpcycling $projet): float
    {
        $base = $projet->devis_accepte?->montant ?? $projet->prix_estime_max ?? 30;

        return (float) (ceil($base * 1.3 / 5) * 5);
    }

    private function descriptionArticle(ProjetUpcycling $projet): string
    {
        $description = $projet->controle_qualite_ia['description_portfolio']
            ?? ($projet->idee_retenue['description'] ?? $projet->produit_final);

        return $description . " Pièce unique upcyclée par {$projet->atelier?->nom} à partir d'un(e) "
            . mb_strtolower($projet->type_vetement) . " en " . mb_strtolower($projet->matiere)
            . ($projet->co2_evite_kg ? ' — environ ' . number_format($projet->co2_evite_kg, 0) . ' kg de CO₂ évités.' : '.');
    }

    /*
    |------------------------------------------------------------------
    | PATCH /upcycling/projets/{projet}/annuler — Annulation (client)
    |------------------------------------------------------------------
    */
    public function annuler(ProjetUpcycling $projet): RedirectResponse
    {
        $this->autoriserClient($projet);

        if (!$projet->estAnnulable()) {
            return back()->with('error', 'Les travaux ont commencé : le projet ne peut plus être annulé.');
        }

        DB::transaction(function () use ($projet) {
            $projet->devis()->where('statut', Devis::STATUT_EN_ATTENTE)->update(['statut' => Devis::STATUT_REFUSE]);
            $this->libererDon($projet);
            $projet->update(['statut' => ProjetUpcycling::STATUT_ANNULE]);
        });

        return back()->with('success', 'Projet annulé.');
    }

    /*
    |------------------------------------------------------------------
    | PATCH /upcycling/projets/{projet}/noter — Avis client
    |------------------------------------------------------------------
    */
    public function noter(Request $request, ProjetUpcycling $projet): RedirectResponse
    {
        $this->autoriserClient($projet);
        abort_unless($projet->statut === ProjetUpcycling::STATUT_TERMINE, 422, 'Le projet doit être terminé pour être noté.');
        abort_if($projet->note_client !== null, 422, 'Vous avez déjà noté ce projet.');

        $request->validate([
            'note_client'        => ['required', 'integer', 'between:1,5'],
            'commentaire_client' => ['nullable', 'string', 'max:500'],
        ], [
            'note_client.required' => 'Veuillez choisir une note.',
            'note_client.between'  => 'La note doit être comprise entre 1 et 5.',
        ]);

        $projet->update($request->only(['note_client', 'commentaire_client']));
        $projet->atelier?->recalculerNote();

        $projet->atelier?->user->notify(new UpcyclingNotification(
            $projet,
            "{$projet->client->full_name} a noté « {$projet->produit_final} » : {$projet->note_client}/5.",
            'fa-star'
        ));

        return back()->with('success', 'Merci pour votre avis !');
    }

    /*
    |------------------------------------------------------------------
    | Helpers
    |------------------------------------------------------------------
    */

    /** Chemin absolu d'une photo (démo dans public/, envois dans storage) */
    private function cheminPhoto(?string $photo): ?string
    {
        if (!$photo) return null;

        return str_starts_with($photo, 'images/')
            ? public_path($photo)
            : Storage::disk('public')->path($photo);
    }

    /** Rend le don au circuit de tri quand le projet est abandonné */
    private function libererDon(ProjetUpcycling $projet): void
    {
        if ($projet->don_vetement_id) {
            DonVetement::whereKey($projet->don_vetement_id)->update(['statut' => 'EN_TRI']);
            $projet->update(['don_vetement_id' => null]);
        }
    }
}
