<?php

namespace App\Notifications;

use App\Models\Tournee;
use Illuminate\Notifications\Notification;

/**
 * M5 Logistique : de nouveaux arrêts ont été ajoutés à la tournée d'un collecteur
 * par l'affectation automatique.
 */
class MissionsAffecteesNotification extends Notification
{
    public function __construct(private Tournee $tournee, private int $nombre)
    {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $arrets = $this->nombre > 1 ? "{$this->nombre} nouveaux arrêts" : 'un nouvel arrêt';

        return [
            // Même format que les autres modules (centre de notifications commun)
            'module'     => 'LOGISTIQUE',
            'icone'      => 'fa-route',
            'message'    => "{$arrets} pour votre tournée « {$this->tournee->zone} » du {$this->tournee->date->format('d/m/Y')}.",
            'url'        => route('logistique.tournees.show', $this->tournee),
            'tournee_id' => $this->tournee->id,
        ];
    }
}
