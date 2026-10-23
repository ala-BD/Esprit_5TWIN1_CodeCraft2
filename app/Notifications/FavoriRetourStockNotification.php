<?php

namespace App\Notifications;

use App\Models\Article;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Notification envoyée quand un article favori revient en stock.
 */
class FavoriRetourStockNotification extends Notification
{
    use Queueable;

    public function __construct(
        public Article $article,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("[RETISS] ✅ Retour en stock — {$this->article->titre}")
            ->greeting('Bonjour ' . ($notifiable->full_name ?: $notifiable->name) . ' !')
            ->line("Un article de votre liste de souhaits est de nouveau disponible !")
            ->line("**{$this->article->titre}** — {$this->article->prix} DT")
            ->action('Voir l\'article', route('articles.show', $this->article))
            ->line('Dépêchez-vous, le stock est limité ! 🌿');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type'       => 'favori_retour_stock',
            'article_id' => $this->article->id,
            'titre'      => $this->article->titre,
            'prix'       => $this->article->prix,
            'url'        => route('articles.show', $this->article),
            'message'    => "Retour en stock : {$this->article->titre} — {$this->article->prix} DT",
        ];
    }
}
