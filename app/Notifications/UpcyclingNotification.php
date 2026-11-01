<?php

namespace App\Notifications;

use App\Models\ProjetUpcycling;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

/**
 * Notification in-app du module M3 (devis, étapes, messages...).
 * Même format que les notifications de commande (clés "message" et "url").
 */
class UpcyclingNotification extends Notification
{
    use Queueable;

    public function __construct(
        public ProjetUpcycling $projet,
        public string $message,
        public string $icone = 'fa-cut',
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'module'     => 'UPCYCLING',
            'projet_id'  => $this->projet->id,
            'message'    => $this->message,
            'icone'      => $this->icone,
            'url'        => route('upcycling.projets.show', $this->projet),
        ];
    }
}
