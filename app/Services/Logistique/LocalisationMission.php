<?php

namespace App\Services\Logistique;

use App\Models\Adresse;
use App\Models\Mission;

/**
 * Détermine où se trouve un arrêt.
 *
 * Par ordre de fiabilité : l'adresse de livraison de la commande liée,
 * l'adresse par défaut du donateur pour une collecte, puis le géocodage
 * de l'adresse écrite.
 */
class LocalisationMission
{
    public const SOURCE_COMMANDE  = 'COMMANDE';
    public const SOURCE_DONATEUR  = 'DONATEUR';
    public const SOURCE_GEOCODAGE = 'GEOCODAGE';
    public const SOURCE_MANUELLE  = 'MANUELLE';

    public function __construct(private Geocodeur $geocodeur)
    {
    }

    /** Complète l'adresse et les coordonnées d'une mission avant son enregistrement */
    public function localiser(Mission $mission): void
    {
        $adresseLiee = $this->adresseLiee($mission);

        // Mission liée sans adresse saisie : on reprend celle de la commande ou du donateur
        if (blank($mission->adresse) && $adresseLiee) {
            $mission->adresse = $adresseLiee['texte'];
        }

        // Coordonnées fournies explicitement (point placé sur la carte, import, jeu de démonstration)
        if ($mission->isDirty(['latitude', 'longitude']) && $mission->latitude !== null && $mission->longitude !== null) {
            $mission->source_localisation ??= self::SOURCE_MANUELLE;

            return;
        }

        // Coordonnées saisies à la main : on ne les écrase pas tant que rien d'autre ne change
        $aRelocaliser = $mission->latitude === null
            || $mission->isDirty(['adresse', 'commande_id', 'don_vetement_id']);

        if (!$aRelocaliser || ($mission->source_localisation === self::SOURCE_MANUELLE && !$mission->isDirty(['adresse', 'commande_id', 'don_vetement_id']))) {
            return;
        }

        // L'adresse liée ne vaut que si la personne n'a pas saisi une autre adresse
        if ($adresseLiee && $adresseLiee['coordonnees'] && $this->memeAdresse($mission->adresse, $adresseLiee['texte'])) {
            $this->appliquer($mission, $adresseLiee['coordonnees'], $adresseLiee['source']);

            return;
        }

        $trouvees = $this->geocodeur->localiser($mission->adresse ?? '');
        $this->appliquer($mission, $trouvees, $trouvees ? self::SOURCE_GEOCODAGE : null);
    }

    /** @return array{texte: string, coordonnees: ?array, source: string}|null */
    private function adresseLiee(Mission $mission): ?array
    {
        if ($mission->commande_id && ($commande = $mission->commande)) {
            $adresse = $commande->adresse;

            return [
                'texte'       => $adresse?->formatted_address ?? (string) $commande->adresse_snapshot,
                'coordonnees' => $this->coordonnees($adresse),
                'source'      => self::SOURCE_COMMANDE,
            ];
        }

        if ($mission->don_vetement_id && ($don = $mission->donVetement)) {
            $adresse = $don->user?->adresseParDefaut;

            if ($adresse) {
                return [
                    'texte'       => $adresse->formatted_address,
                    'coordonnees' => $this->coordonnees($adresse),
                    'source'      => self::SOURCE_DONATEUR,
                ];
            }
        }

        return null;
    }

    private function coordonnees(?Adresse $adresse): ?array
    {
        return $adresse && $adresse->latitude !== null && $adresse->longitude !== null
            ? ['latitude' => (float) $adresse->latitude, 'longitude' => (float) $adresse->longitude]
            : null;
    }

    private function memeAdresse(?string $a, string $b): bool
    {
        $normaliser = fn (?string $t) => mb_strtolower(preg_replace('/\s+/', ' ', trim((string) $t)));

        return $normaliser($a) === $normaliser($b);
    }

    private function appliquer(Mission $mission, ?array $coordonnees, ?string $source): void
    {
        $mission->latitude            = $coordonnees['latitude'] ?? null;
        $mission->longitude           = $coordonnees['longitude'] ?? null;
        $mission->source_localisation = $source;
    }
}
