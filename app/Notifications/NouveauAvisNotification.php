<?php

namespace App\Notifications;

use App\Models\Avis;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NouveauAvisNotification extends Notification
{
    use Queueable;

    public function __construct(
        public Avis $avis
    ) {}

    /**
     * Get the notification's delivery channels.
     */
    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    /**
     * Email notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $article = $this->avis->article;
        $author  = $this->avis->user;

        $message = (new MailMessage)
            ->subject("[RETISS] Nouvel avis reçu pour votre article « {$article->titre} » ⭐")
            ->greeting("Bonjour " . ($notifiable->full_name ?: $notifiable->name) . ",")
            ->line("Un nouvel avis vient d'être publié par **{$author->name}** sur votre article **« {$article->titre} »**.")
            ->line("Note globale attribuée : **⭐ " . number_format($this->avis->note_globale, 1) . " / 5**");

        if ($this->avis->commentaire) {
            $message->line("Commentaire : « {$this->avis->commentaire} »");
        }

        $message->action('Consulter mon article et les avis', route('articles.show', $article))
            ->line("Merci pour votre engagement dans la communauté RETISS ! 🌿");

        return $message;
    }

    /**
     * In-app database notification.
     */
    public function toArray(object $notifiable): array
    {
        $article = $this->avis->article;
        $author  = $this->avis->user;

        return [
            'avis_id'      => $this->avis->id,
            'article_id'   => $article->id,
            'article_titre'=> $article->titre,
            'auteur_id'    => $author->id,
            'auteur_nom'   => $author->name,
            'note_globale' => $this->avis->note_globale,
            'commentaire'  => $this->avis->commentaire,
            'message'      => "{$author->name} a laissé une note de ⭐ " . number_format($this->avis->note_globale, 1) . "/5 sur « {$article->titre} ».",
            'url'          => route('articles.show', $article),
        ];
    }
}
