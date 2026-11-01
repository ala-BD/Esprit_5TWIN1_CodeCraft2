<?php

namespace App\Services\Upcycling;

use App\Models\Atelier;
use App\Models\ProjetUpcycling;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

/**
 * Matching atelier : classe les ateliers actifs pour un projet donné.
 *
 * Score sur 100 :
 *  - Spécialité correspondant à la catégorie du produit       : 30 pts
 *  - Affinité sémantique idée ↔ savoir-faire (embeddings IA)  : 20 pts
 *  - Note moyenne des clients (sur 5)                          : 20 pts
 *  - Tarif horaire (le moins cher obtient le maximum)          : 15 pts
 *  - Disponibilité (moins de projets en cours = mieux)         : 15 pts
 */
class MatchingAtelierService
{
    const POIDS_SPECIALITE    = 30;
    const POIDS_AFFINITE      = 20;
    const POIDS_NOTE          = 20;
    const POIDS_TARIF         = 15;
    const POIDS_DISPONIBILITE = 15;

    /** Note neutre attribuée à un atelier encore jamais noté */
    const NOTE_PAR_DEFAUT = 3.0;

    /** Au-delà de ce nombre de projets en cours, l'atelier n'a plus de points de disponibilité */
    const CHARGE_MAX = 5;

    /**
     * Avec gemini-embedding-001, les textes en français d'un même domaine ont tous
     * un cosinus élevé (0,68 à 0,87 mesuré) : seul l'écart entre ateliers est parlant.
     * L'affinité est donc relative (le plus proche = 1, le plus éloigné = 0).
     * Si les écarts sont inférieurs à ECART_MIN, ils ne sont pas significatifs.
     */
    const ECART_MIN = 0.02;

    /** Méthode utilisée lors du dernier classement : GEMINI (embeddings) ou LEXICALE */
    public string $methodeAffinite = 'LEXICALE';

    public function __construct(private GeminiClient $gemini) {}

    /**
     * @return Collection<int, array{atelier: Atelier, score: int, details: array<string, int>}>
     */
    public function classer(ProjetUpcycling $projet): Collection
    {
        $ateliers = Atelier::where('actif', true)
            ->withCount(['projetUpcyclings as charge_en_cours' => fn ($q) => $q->whereIn('statut', ProjetUpcycling::STATUTS_EN_COURS)])
            ->get();

        if ($ateliers->isEmpty()) {
            return collect();
        }

        $tarifMin  = max(1, $ateliers->min('tarif_horaire'));
        $affinites = $this->affinites($projet, $ateliers);

        return $ateliers->map(function (Atelier $atelier) use ($projet, $tarifMin, $affinites) {
            $note = $atelier->note_moyenne > 0 ? $atelier->note_moyenne : self::NOTE_PAR_DEFAUT;

            $details = [
                'specialite'    => $projet->categorie_produit && $atelier->specialite === $projet->categorie_produit
                                       ? self::POIDS_SPECIALITE : 0,
                'affinite'      => (int) round(($affinites[$atelier->id] ?? 0) * self::POIDS_AFFINITE),
                'note'          => (int) round($note / 5 * self::POIDS_NOTE),
                'tarif'         => (int) round($tarifMin / max(1, $atelier->tarif_horaire) * self::POIDS_TARIF),
                'disponibilite' => (int) round(max(0, 1 - $atelier->charge_en_cours / self::CHARGE_MAX) * self::POIDS_DISPONIBILITE),
            ];

            return [
                'atelier' => $atelier,
                'score'   => array_sum($details),
                'details' => $details,
            ];
        })->sortByDesc('score')->values();
    }

    /*
    |------------------------------------------------------------------
    | Affinité sémantique (0 à 1 par atelier)
    |------------------------------------------------------------------
    */

    /** @return array<int, float> affinité par id d'atelier */
    private function affinites(ProjetUpcycling $projet, Collection $ateliers): array
    {
        $texteProjet = $this->texteProjet($projet);

        if ($this->gemini->estConfigure()) {
            try {
                $resultat = $this->affinitesGemini($texteProjet, $ateliers);
                $this->methodeAffinite = 'GEMINI';
                return $resultat;
            } catch (Throwable $e) {
                Log::warning('Upcycling IA : embeddings indisponibles, affinité lexicale.', ['erreur' => $e->getMessage()]);
            }
        }

        $this->methodeAffinite = 'LEXICALE';
        $motsProjet = $this->mots($texteProjet);

        return $ateliers->mapWithKeys(function (Atelier $atelier) use ($motsProjet) {
            $motsAtelier = $this->mots($this->texteAtelier($atelier));
            $communs = count(array_intersect($motsProjet, $motsAtelier));
            $union   = count(array_unique(array_merge($motsProjet, $motsAtelier)));

            // Jaccard amplifié : deux textes courts partagent rarement plus de 30 % de mots
            return [$atelier->id => $union ? min(1, $communs / $union * 3) : 0];
        })->all();
    }

    private function affinitesGemini(string $texteProjet, Collection $ateliers): array
    {
        // Les vecteurs des ateliers sont mis en cache tant que leur texte ne change pas
        $aCalculer = $ateliers->filter(
            fn (Atelier $a) => !$a->embedding_ia || $a->embedding_empreinte !== md5($this->texteAtelier($a))
        )->values();

        $vecteurs = $this->gemini->embeddings(
            array_merge([$texteProjet], $aCalculer->map(fn ($a) => $this->texteAtelier($a))->all())
        );
        $vecteurProjet = array_shift($vecteurs);

        foreach ($aCalculer as $i => $atelier) {
            $atelier->forceFill([
                'embedding_ia'        => $vecteurs[$i],
                'embedding_empreinte' => md5($this->texteAtelier($atelier)),
            ])->saveQuietly();
        }

        $cosinus = $ateliers->mapWithKeys(
            fn (Atelier $atelier) => [$atelier->id => GeminiClient::cosinus($vecteurProjet, $atelier->embedding_ia)]
        );

        $min = $cosinus->min();
        $ecart = $cosinus->max() - $min;

        return $cosinus->map(fn ($valeur) => $ecart < self::ECART_MIN ? 0.5 : ($valeur - $min) / $ecart)->all();
    }

    private function texteProjet(ProjetUpcycling $projet): string
    {
        $idee = $projet->idee_retenue;

        return trim(implode('. ', array_filter([
            $projet->produit_final,
            Atelier::SPECIALITES[$projet->categorie_produit] ?? null,
            $idee['description'] ?? null,
            "à partir d'un(e) {$projet->type_vetement} en {$projet->matiere}",
        ])));
    }

    /** Savoir-faire de l'atelier : spécialité, présentation et dernières réalisations */
    private function texteAtelier(Atelier $atelier): string
    {
        $realisations = $atelier->projetUpcyclings()
            ->where('statut', ProjetUpcycling::STATUT_TERMINE)
            ->latest('date_fin')->take(5)->pluck('produit_final')->filter()->implode(', ');

        return trim(implode('. ', array_filter([
            $atelier->nom,
            Atelier::SPECIALITES[$atelier->specialite] ?? $atelier->specialite,
            $atelier->description,
            $realisations ? "Réalisations : {$realisations}" : null,
        ])));
    }

    /** Mots significatifs (minuscules, sans accents, 4 lettres et plus) */
    private function mots(string $texte): array
    {
        return array_values(array_unique(array_filter(
            preg_split('/[^a-z]+/', Str::of($texte)->lower()->ascii()->toString()),
            fn ($mot) => strlen($mot) >= 4
        )));
    }
}
