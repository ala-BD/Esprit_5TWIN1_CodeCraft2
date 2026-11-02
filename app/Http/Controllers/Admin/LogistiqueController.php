<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Commande;
use App\Models\DonVetement;
use App\Models\Mission;
use App\Models\PositionCollecteur;
use App\Models\Tournee;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class LogistiqueController extends Controller
{
    /*
    |------------------------------------------------------------------
    | GET /admin/logistique — Vue d'ensemble des tournées de tous les collecteurs
    |------------------------------------------------------------------
    */
    public function index(Request $request): View
    {
        $tournees = Tournee::with('user')
            ->withCount([
                'missions',
                'missions as missions_terminees_count' => fn (Builder $q) => $q->where('statut', Mission::STATUT_TERMINEE),
                'missions as missions_echouees_count'  => fn (Builder $q) => $q->where('statut', Mission::STATUT_ECHOUEE),
            ])
            ->when($request->filled('collecteur'), fn (Builder $q) => $q->where('user_id', $request->collecteur))
            ->when(array_key_exists((string) $request->statut, Tournee::STATUTS), fn (Builder $q) => $q->where('statut', $request->statut))
            ->when($request->filled('date'), fn (Builder $q) => $q->whereDate('date', $request->date))
            ->orderByDesc('date')
            ->latest('id')
            ->paginate(15)
            ->withQueryString();

        $missionsDuJour = Mission::whereHas('tournee', fn (Builder $q) => $q->whereDate('date', today()));

        $stats = [
            'tournees_du_jour' => Tournee::whereDate('date', today())->count(),
            'missions_du_jour' => (clone $missionsDuJour)->count(),
            'terminees'        => (clone $missionsDuJour)->where('statut', Mission::STATUT_TERMINEE)->count(),
            'echouees'         => (clone $missionsDuJour)->where('statut', Mission::STATUT_ECHOUEE)->count(),
            'non_localisees'   => Mission::whereNull('latitude')
                ->whereIn('statut', [Mission::STATUT_A_FAIRE, Mission::STATUT_EN_COURS])->count(),
        ];

        return view('admin.logistique.index', [
            'tournees'    => $tournees,
            'stats'       => $stats,
            'collecteurs' => User::where('role', User::ROLE_COLLECTEUR)->orderBy('prenom')->get(),
            'aPlanifier'  => self::aPlanifier(),
        ]);
    }

    /*
    |------------------------------------------------------------------
    | GET /admin/logistique/tournees/{tournee} — Détail d'une tournée (lecture seule)
    |------------------------------------------------------------------
    */
    public function show(Tournee $tournee): View
    {
        $tournee->load(['user', 'missions.commande', 'missions.donVetement']);

        return view('admin.logistique.show', compact('tournee'));
    }

    /*
    |------------------------------------------------------------------
    | GET /admin/logistique/carte — Carte en direct des tournées du jour
    |------------------------------------------------------------------
    */
    public function carte(Request $request): View
    {
        return view('admin.logistique.carte', [
            'date'    => $this->dateDemandee($request),
            'donnees' => $this->etatDuJour($this->dateDemandee($request)),
        ]);
    }

    /*
    |------------------------------------------------------------------
    | GET /admin/logistique/carte/donnees — Même contenu en JSON (rafraîchissement)
    |------------------------------------------------------------------
    */
    public function donneesCarte(Request $request): JsonResponse
    {
        return response()->json($this->etatDuJour($this->dateDemandee($request)));
    }

    private function dateDemandee(Request $request): Carbon
    {
        $date = $request->date('date');

        return ($date ?? today())->startOfDay();
    }

    /** Tournées du jour avec leurs arrêts, leur avancement, leur retard et la position du collecteur */
    private function etatDuJour(Carbon $date): array
    {
        $maintenant = now();
        $estAujourdhui = $date->isSameDay($maintenant);

        $tournees = Tournee::with(['user', 'missions'])
            ->whereDate('date', $date)
            ->where('statut', '!=', Tournee::STATUT_ANNULEE)
            ->orderBy('id')
            ->get();

        // Dernière position de chaque collecteur concerné, enregistrée ce jour-là
        $positions = PositionCollecteur::whereIn('user_id', $tournees->pluck('user_id'))
            ->whereDate('enregistree_le', $date)
            ->orderBy('enregistree_le')
            ->get()
            ->keyBy('user_id');

        return [
            'date'        => $date->format('Y-m-d'),
            'mis_a_jour'  => $maintenant->format('H:i:s'),
            'depot'       => ['lat' => (float) config('logistique.depot.latitude'), 'lng' => (float) config('logistique.depot.longitude'), 'nom' => config('logistique.depot.nom')],
            'tournees'    => $tournees->map(function (Tournee $t) use ($positions, $maintenant, $estAujourdhui) {
                $missions  = $t->missions;
                $faites    = $missions->whereIn('statut', [Mission::STATUT_TERMINEE, Mission::STATUT_ECHOUEE]);
                $prochain  = $missions->firstWhere('statut', Mission::STATUT_EN_COURS) ?? $missions->firstWhere('statut', Mission::STATUT_A_FAIRE);
                $position  = $positions->get($t->user_id);

                // Retard : l'heure prévue du prochain arrêt est déjà passée
                $retard = 0;
                if ($estAujourdhui && $prochain) {
                    $prevue = $t->date->copy()->setTimeFromTimeString($prochain->heure);
                    $retard = $maintenant->gt($prevue) ? (int) $prevue->diffInMinutes($maintenant) : 0;
                }

                return [
                    'id'         => $t->id,
                    'zone'       => $t->zone,
                    'statut'     => $t->statut,
                    'url'        => route('admin.logistique.show', $t),
                    'collecteur' => ['nom' => $t->user?->full_name, 'initiales' => $t->user?->initials, 'telephone' => $t->user?->telephone],
                    'total'      => $missions->count(),
                    'faites'     => $faites->count(),
                    'echouees'   => $missions->where('statut', Mission::STATUT_ECHOUEE)->count(),
                    'retard'     => $retard,
                    'prochain'   => $prochain ? ['ordre' => $prochain->ordre, 'adresse' => $prochain->adresse, 'heure' => $prochain->heure, 'statut' => $prochain->statut] : null,
                    'position'   => $position ? [
                        'lat' => $position->latitude, 'lng' => $position->longitude,
                        'il_y_a' => (int) $position->enregistree_le->diffInMinutes($maintenant),
                        'en_direct' => $position->estEnDirect(),
                    ] : null,
                    'arrets'     => $missions->filter->estLocalisee()->map(fn (Mission $m) => [
                        'lat' => $m->latitude, 'lng' => $m->longitude, 'ordre' => $m->ordre, 'statut' => $m->statut,
                        'adresse' => $m->adresse, 'heure' => $m->heure,
                    ])->values(),
                ];
            })->values(),
        ];
    }

    /**
     * Ce qui attend un transport et n'est dans aucune mission active :
     * les commandes à livrer et les dons à collecter.
     *
     * @return array{commandes: \Illuminate\Support\Collection, dons: \Illuminate\Support\Collection}
     */
    public static function aPlanifier(): array
    {
        $prisEnCharge = fn (string $colonne) => Mission::whereNotNull($colonne)
            ->where('statut', '!=', Mission::STATUT_ECHOUEE)
            ->select($colonne);

        return [
            'commandes' => Commande::with(['user', 'adresse'])
                ->whereIn('statut', [Commande::STATUT_CONFIRMEE, Commande::STATUT_EN_PREPARATION])
                ->whereNotIn('id', $prisEnCharge('commande_id'))
                ->orderBy('date_commande')
                ->get(),
            'dons' => DonVetement::with('user.adresseParDefaut')
                ->where('statut', 'DEPOSE')
                ->whereNotIn('id', $prisEnCharge('don_vetement_id'))
                ->orderBy('created_at')
                ->get(),
        ];
    }
}
