<?php

namespace App\Services\Logistique;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Transforme une adresse écrite en coordonnées GPS, avec le service gratuit
 * Nominatim d'OpenStreetMap.
 *
 * Règles d'usage de Nominatim respectées : un User-Agent identifiable, au plus
 * une requête par seconde, et chaque résultat mis en cache (y compris « introuvable »).
 */
class Geocodeur
{
    /** Délai minimal entre deux requêtes, en microsecondes */
    private const INTERVALLE = 1_100_000;

    /** @return array{latitude: float, longitude: float}|null */
    public function localiser(string $adresse): ?array
    {
        $adresse = trim(preg_replace('/\s+/', ' ', $adresse));

        if (!config('logistique.geocodage.actif') || $adresse === '') {
            return null;
        }

        $cle = 'geocodage:' . md5(Str::lower(Str::ascii($adresse)));

        // On garde aussi les échecs (valeur false) pour ne pas redemander la même adresse
        $resultat = Cache::rememberForever($cle, fn () => $this->interroger($adresse) ?? false);

        return $resultat ?: null;
    }

    private function interroger(string $adresse): ?array
    {
        $this->patienter();

        try {
            $reponse = Http::withHeaders(['User-Agent' => config('logistique.geocodage.user_agent')])
                ->timeout(8)
                ->acceptJson()
                ->get(config('logistique.geocodage.url'), [
                    'q'            => $adresse,
                    'format'       => 'jsonv2',
                    'limit'        => 1,
                    'countrycodes' => config('logistique.geocodage.pays'),
                ]);
        } catch (\Throwable $e) {
            Log::info('Géocodage indisponible', ['adresse' => $adresse, 'erreur' => $e->getMessage()]);

            return null;
        }

        $premier = $reponse->successful() ? $reponse->json('0') : null;

        if (!is_array($premier) || !isset($premier['lat'], $premier['lon'])) {
            return null;
        }

        return ['latitude' => (float) $premier['lat'], 'longitude' => (float) $premier['lon']];
    }

    /** Espace les requêtes d'au moins une seconde, même entre plusieurs processus */
    private function patienter(): void
    {
        $dernier = (int) Cache::get('geocodage:derniere-requete', 0);
        $attente = self::INTERVALLE - ((int) (microtime(true) * 1_000_000) - $dernier);

        if ($attente > 0) {
            usleep($attente);
        }

        Cache::put('geocodage:derniere-requete', (int) (microtime(true) * 1_000_000), 60);
    }
}
