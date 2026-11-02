<?php

namespace App\Notifications;

use App\Models\Mission;
use Illuminate\Notifications\Notification;

/**
 * M5 Logistique : un arrêt n'a pas pu être fait. Les administrateurs sont prévenus
 * pour replanifier (la commande ou le don repasse dans « À planifier »).
 */
class MissionEchoueeNotification extends Notification
{
    public function __construct(private Mission $mission)
    {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $tournee = $this->mission->tournee;
        $raison = $this->mission->preuve_livraison ?: 'raison non précisée';

        return [
            'module'     => 'LOGISTIQUE',
            'icone'      => 'fa-triangle-exclamation',
            'message'    => "Échec de la {$this->libelleType()} n° {$this->mission->ordre} ({$this->mission->adresse}) — {$raison}. "
                . "Tournée « {$tournee?->zone} » de {$tournee?->user?->full_name} : à replanifier.",
            'url'        => $tournee ? route('admin.logistique.show', $tournee) : route('admin.logistique.index'),
            'mission_id' => $this->mission->id,
        ];
    }

    private function libelleType(): string
    {
        return $this->mission->type === Mission::TYPE_COLLECTE ? 'collecte' : 'livraison';
    }
}
