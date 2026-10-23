<?php

namespace App\Notifications;

use App\Models\Article;
use App\Models\RechercheSauvegardee;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Notification envoyée quand un nouvel article correspond à une recherche sauvegardée.
 */
class NouvelArticleRechercheNotification extends Notification
{
    use Queueable;

    public function __construct(
        public Article $article,
        public RechercheSauvegardee $recherche,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("[RETISS] 🆕 Nouvel article pour «&nbsp;{$this->recherche->nom}&nbsp;»")
            ->greeting('Bonjour ' . ($notifiable->full_name ?: $notifiable->name) . ' !')
            ->line("Un nouvel article correspond à votre recherche sauvegardée **«&nbsp;{$this->recherche->nom}&nbsp;»**.")
            ->line("**{$this->article->titre}** — {$this->article->prix} DT")
            ->action('Voir l\'article', route('articles.show', $this->article))
            ->line('Bonne découverte sur RETISS ! 🌿');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type'         => 'nouvel_article_recherche',
            'article_id'   => $this->article->id,
            'titre'        => $this->article->titre,
            'prix'         => $this->article->prix,
            'recherche_id' => $this->recherche->id,
            'recherche_nom'=> $this->recherche->nom,
            'url'          => route('articles.show', $this->article),
            'message'      => "Nouvel article pour «&nbsp;{$this->recherche->nom}&nbsp;» : {$this->article->titre} — {$this->article->prix} DT",
        ];
    }
}
