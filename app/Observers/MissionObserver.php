<?php

namespace App\Observers;

use App\Models\Mission;
use App\Services\Logistique\LocalisationMission;
use App\Services\Logistique\SynchronisationMission;

/**
 * Garde les missions cohérentes d'où que vienne la modification
 * (formulaire, assistant vocal, mode tournée…).
 */
class MissionObserver
{
    public function __construct(
        private LocalisationMission $localisation,
        private SynchronisationMission $synchronisation,
    ) {
    }

    public function saving(Mission $mission): void
    {
        $this->localisation->localiser($mission);

        // Heure de fin réelle : posée à la clôture, retirée si la mission est rouverte
        if ($mission->isDirty('statut') || !$mission->exists) {
            $mission->terminee_le = in_array($mission->statut, [Mission::STATUT_TERMINEE, Mission::STATUT_ECHOUEE], true)
                ? ($mission->terminee_le ?? now())
                : null;
        }
    }

    public function saved(Mission $mission): void
    {
        if ($mission->wasRecentlyCreated || $mission->wasChanged('statut')) {
            $this->synchronisation->apresChangementDeStatut($mission);
        }
    }
}
