<?php

namespace App\Services\Logistique;

use App\Models\Tournee;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Explique en langage courant l'itinéraire proposé par l'optimiseur.
 *
 * Le calcul reste celui de l'algorithme : le modèle de langage (même configuration
 * que l'assistant vocal) se contente de le commenter pour le collecteur. Sans IA
 * disponible, un résumé chiffré est rédigé directement.
 */
class ExplicationItineraire
{
    public function expliquer(Tournee $tournee, array $proposition): string
    {
        if ($proposition['etapes']->isEmpty()) {
            return "Aucun arrêt localisé à réordonner pour l'instant.";
        }

        // Même proposition, même explication : pas de nouvel appel à chaque affichage
        return Cache::remember(
            'itineraire:explication:' . $proposition['empreinte'],
            now()->addHour(),
            fn () => $this->parIa($tournee, $proposition) ?? $this->resume($proposition),
        );
    }

    /** Résumé sans IA, à partir des chiffres */
    public function resume(array $proposition): string
    {
        $etapes  = $proposition['etapes'];
        $premier = $etapes->first();
        $dernier = $etapes->last();

        $texte = $proposition['gain_km'] > 0.05
            ? "Ce nouvel ordre raccourcit le trajet de {$proposition['gain_km']} km ({$proposition['gain_pct']} %), "
              . "soit {$proposition['distance_apres']} km au lieu de {$proposition['distance_avant']} km. "
            : "L'ordre actuel était déjà le plus court trouvé ({$proposition['distance_apres']} km). ";

        return $texte . "Premier arrêt vers {$premier['arrivee']->format('H:i')} ({$this->lieu($premier['mission']->adresse)}), "
            . "dernier vers {$dernier['arrivee']->format('H:i')} ({$this->lieu($dernier['mission']->adresse)}).";
    }

    private function parIa(Tournee $tournee, array $proposition): ?string
    {
        if (blank(config('assistant.api_key'))) {
            return null;
        }

        $lignes = $proposition['etapes']->map(fn (array $e, int $i) => sprintf(
            '%d. %s à %s (%s km depuis l\'arrêt précédent) — %s',
            $i + 1,
            $e['mission']->type === 'COLLECTE' ? 'Collecte' : 'Livraison',
            $e['arrivee']->format('H:i'),
            $e['km'],
            $e['mission']->adresse,
        ))->join("\n");

        $plusLong = $proposition['etapes']->sortByDesc('km')->first();
        $fin = $proposition['etapes']->last()['fin'];

        $donnees = <<<TXT
        Tournée « {$tournee->zone} » du {$tournee->date->format('d/m/Y')}.
        Départ : {$proposition['depart']['nom']} à {$proposition['heure_depart']->format('H:i')}.
        Retour au dépôt : {$this->ouiNon($proposition['retour'])}.
        Distance avant optimisation : {$proposition['distance_avant']} km. Après : {$proposition['distance_apres']} km (gain {$proposition['gain_pct']} %).
        Arrêts sans position connue (placés à la fin) : {$proposition['non_localisees']->count()}.
        Plus long trajet entre deux arrêts : {$plusLong['km']} km, pour rejoindre {$plusLong['mission']->adresse}.
        Fin du dernier arrêt prévue vers {$fin->format('H:i')}.
        Nouvel ordre :
        {$lignes}
        TXT;

        try {
            $reponse = Http::withToken(config('assistant.api_key'))
                ->timeout(config('assistant.timeout'))
                ->acceptJson()
                ->post(config('assistant.base_url') . '/chat/completions', [
                    'model'       => config('assistant.model'),
                    'temperature' => 0.3,
                    'max_completion_tokens' => 400,
                    ...config('assistant.options', []),
                    'messages'    => [
                        ['role' => 'system', 'content' => 'Tu es le répartiteur d\'une plateforme de collecte et de livraison de vêtements à Tunis. '
                            . 'Tu expliques à un collecteur, en français simple, l\'itinéraire calculé pour sa tournée. '
                            . 'Trois phrases au maximum, un seul paragraphe, sans liste ni mise en forme. '
                            . 'Dis le gain obtenu et l\'enchaînement des quartiers dans l\'ordre, en les nommant tels qu\'ils figurent dans les données, '
                            . 'puis un point d\'attention tiré uniquement des données : le plus long trajet, l\'heure de fin ou les arrêts sans position. '
                            . 'N\'invente aucun chiffre ni aucun lieu. Ne décris ni la géographie (côte, intérieur, points cardinaux) ni le trafic : tu ne les connais pas. Vouvoie le collecteur.'],
                        ['role' => 'user', 'content' => $donnees],
                    ],
                ]);
        } catch (\Throwable $e) {
            return null;
        }

        $texte = trim((string) $reponse->json('choices.0.message.content'));

        if ($reponse->failed() || $texte === '') {
            Log::info('Explication d\'itinéraire : IA indisponible', ['statut' => $reponse->status()]);

            return null;
        }

        return $texte;
    }

    /** Partie lisible d'une adresse : la ville ou le quartier */
    private function lieu(string $adresse): string
    {
        $parties = array_map('trim', explode(',', $adresse));

        return preg_replace('/^\d{4}\s+/', '', end($parties)) ?: $adresse;
    }

    private function ouiNon(mixed $valeur): string
    {
        return $valeur ? 'oui' : 'non';
    }
}
