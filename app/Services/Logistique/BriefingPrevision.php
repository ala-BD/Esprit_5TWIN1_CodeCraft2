<?php

namespace App\Services\Logistique;

use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

/**
 * Commente la prévision des dons pour l'administrateur : tendance, zones à surveiller,
 * moyens à prévoir. Les chiffres viennent du modèle statistique ; le modèle de langage
 * (même configuration que l'assistant vocal) ne fait que les mettre en mots.
 */
class BriefingPrevision
{
    /** Capacité de la semaine à venir : arrêts à prévoir et journées de collecteur nécessaires */
    public function capacite(array $prevision): array
    {
        $prochaine  = $prevision['total']['prevision'][0] ?? ['valeur' => 0, 'haut' => 0];
        $parJour    = (int) config('logistique.affectation.arrets_max');
        $collecteurs = User::where('role', User::ROLE_COLLECTEUR)->where('actif', true)->count();

        return [
            'arrets'          => (int) round($prochaine['valeur']),
            'arrets_haut'     => (int) ceil($prochaine['haut']),
            'journees'        => (int) ceil($prochaine['valeur'] / max(1, $parJour)),
            'journees_haut'   => (int) ceil($prochaine['haut'] / max(1, $parJour)),
            'collecteurs'     => $collecteurs,
            'journees_dispo'  => $collecteurs * 5,
        ];
    }

    public function rediger(array $prevision): string
    {
        $capacite = $this->capacite($prevision);
        $donnees = $this->donnees($prevision, $capacite);

        return Cache::remember('prevision:briefing:' . md5($donnees), now()->addHour(),
            fn () => $this->parIa($donnees) ?? $this->resume($prevision, $capacite));
    }

    /** Version sans IA, rédigée à partir des chiffres */
    public function resume(array $prevision, array $capacite): string
    {
        $total = $prevision['total'];
        $prochaine = $total['prevision'][0] ?? null;

        if (!$prochaine) {
            return "Pas encore assez d'historique pour prévoir les dons.";
        }

        $sens = match (true) {
            $total['tendance_pct'] >= 5  => "en hausse de {$total['tendance_pct']} %",
            $total['tendance_pct'] <= -5 => 'en baisse de ' . abs($total['tendance_pct']) . ' %',
            default                      => 'stable',
        };
        $zone = $prevision['zones']->first();

        return "Environ {$capacite['arrets']} dons attendus cette semaine (entre " . (int) round($prochaine['bas']) . " et {$capacite['arrets_haut']}), "
            . "{$sens} par rapport aux quatre dernières semaines. "
            . ($zone ? "{$zone['zone']} reste la zone la plus active. " : '')
            . "Il faut prévoir {$capacite['journees']} journée(s) de collecte, sur {$capacite['journees_dispo']} disponibles.";
    }

    private function donnees(array $prevision, array $capacite): string
    {
        $total = $prevision['total'];
        $semaines = collect($total['prevision'])->take(4)->map(fn ($p) => sprintf(
            'semaine du %s : %s dons (entre %s et %s)', Carbon::parse($p['semaine'])->format('d/m'), $p['valeur'], $p['bas'], $p['haut']
        ))->join('; ');

        $zones = $prevision['zones']->map(fn ($z) => sprintf(
            '%s : %s dons prévus cette semaine, moyenne des 4 dernières semaines %s, évolution %+d %%, erreur mesurée %s',
            $z['zone'], $z['prevision'][0]['valeur'] ?? 0, $z['moyenne_4'], $z['tendance_pct'], $z['erreur_pct'] !== null ? $z['erreur_pct'] . ' %' : 'non mesurée'
        ))->join("\n");

        $saison = $prevision['saisonnalite'] ? 'oui (appris sur la dernière année)' : 'non (moins d\'un an d\'historique)';

        return <<<TXT
        Historique : {$prevision['semaines_historique']} semaines. Saisonnalité prise en compte : {$saison}.
        Total, prochaines semaines : {$semaines}.
        Moyenne des 4 dernières semaines : {$total['moyenne_4']} dons. Évolution de la semaine prochaine : {$total['tendance_pct']} %.
        Erreur mesurée du modèle sur le total (8 semaines cachées) : {$total['erreur_pct']} %.
        Par gouvernorat :
        {$zones}
        Besoin : {$capacite['arrets']} arrêts de collecte prévus cette semaine, soit {$capacite['journees']} journée(s) de collecteur.
        Hypothèse haute de l'intervalle : {$capacite['arrets_haut']} arrêts, soit {$capacite['journees_haut']} journée(s).
        Moyens : {$capacite['collecteurs']} collecteurs actifs, {$capacite['journees_dispo']} journées de collecte disponibles dans la semaine.
        TXT;
    }

    private function parIa(string $donnees): ?string
    {
        if (blank(config('assistant.api_key'))) {
            return null;
        }

        try {
            $reponse = Http::withToken(config('assistant.api_key'))
                ->timeout(config('assistant.timeout'))
                ->acceptJson()
                ->post(config('assistant.base_url') . '/chat/completions', [
                    'model'       => config('assistant.model'),
                    'temperature' => 0.3,
                    'max_completion_tokens' => 500,
                    ...config('assistant.options', []),
                    'messages'    => [
                        ['role' => 'system', 'content' => "Tu es l'analyste logistique d'une plateforme de dons de vêtements à Tunis. "
                            . "À partir des prévisions fournies, rédige pour l'administrateur un briefing de quatre phrases au maximum, en un seul paragraphe, "
                            . 'sans liste ni mise en forme : volume attendu et tendance, zone(s) à surveiller, moyens à prévoir (journées de collecteur), '
                            . "et la fiabilité (signale si l'erreur mesurée d'une zone est élevée). "
                            . "Utilise uniquement les chiffres fournis, arrondis à l'unité ; n'invente aucune cause (météo, fêtes, événements)."],
                        ['role' => 'user', 'content' => $donnees],
                    ],
                ]);
        } catch (\Throwable $e) {
            return null;
        }

        $texte = trim((string) $reponse->json('choices.0.message.content'));

        return $reponse->successful() && $texte !== '' ? $texte : null;
    }
}
