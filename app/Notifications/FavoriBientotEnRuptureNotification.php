<?php

namespace App\Notifications;

use App\Models\Article;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Notification envoyée quand un article favori est bientôt en rupture de stock (<= 2).
 */
class FavoriBientotEnRuptureNotification extends Notification
{
    use Queueable;

    public function __construct(
        public Article $article,
        public int $stockRestant,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("[RETISS] ⚠️ Plus que {$this->stockRestant} exemplaire(s) — {$this->article->titre}")
            ->greeting('Bonjour ' . ($notifiable->full_name ?: $notifiable->name) . ' !')
            ->line("Attention : un article de votre liste de souhaits est bientôt en rupture !")
            ->line("**{$this->article->titre}** — {$this->article->prix} DT")
            ->line("Il ne reste plus que **{$this->stockRestant} exemplaire(s)** en stock.")
            ->action('Commander maintenant', route('articles.show', $this->article))
            ->line('Ne manquez pas cette pièce unique ! 🌿');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type'          => 'favori_bientot_rupture',
            'article_id'    => $this->article->id,
            'titre'         => $this->article->titre,
            'prix'          => $this->article->prix,
            'stock_restant' => $this->stockRestant,
            'url'           => route('articles.show', $this->article),
            'message'       => "Stock presque épuisé : {$this->article->titre} (plus que {$this->stockRestant} restants)",
        ];
    }
}
