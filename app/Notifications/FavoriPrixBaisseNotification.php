<?php

namespace App\Notifications;

use App\Models\Article;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Notification envoyée quand un article favori change de prix.
 */
class FavoriPrixBaisseNotification extends Notification
{
    use Queueable;

    public function __construct(
        public Article $article,
        public float $ancienPrix,
        public float $nouveauPrix,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $reduction = round((1 - $this->nouveauPrix / $this->ancienPrix) * 100);
        return (new MailMessage)
            ->subject("[RETISS] 🎉 Baisse de prix — {$this->article->titre}")
            ->greeting('Bonne nouvelle ' . ($notifiable->full_name ?: $notifiable->name) . ' !')
            ->line("Un article de votre liste de souhaits vient de baisser de prix de **{$reduction}%** !")
            ->line("**{$this->article->titre}**")
            ->line("Prix avant : ~~{$this->ancienPrix} DT~~ → Nouveau prix : **{$this->nouveauPrix} DT**")
            ->action('Voir l\'article', route('articles.show', $this->article))
            ->line('Profitez-en avant que le stock ne s\'épuise ! 🌿');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type'        => 'favori_prix_baisse',
            'article_id'  => $this->article->id,
            'titre'       => $this->article->titre,
            'ancien_prix' => $this->ancienPrix,
            'nouveau_prix'=> $this->nouveauPrix,
            'url'         => route('articles.show', $this->article),
            'message'     => "Prix réduit : {$this->article->titre} — {$this->ancienPrix} DT → {$this->nouveauPrix} DT",
        ];
    }
}
