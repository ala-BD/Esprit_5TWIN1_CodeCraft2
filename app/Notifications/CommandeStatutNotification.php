<?php

namespace App\Notifications;

use App\Models\Commande;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class CommandeStatutNotification extends Notification
{
    use Queueable;

    public function __construct(
        public Commande $commande,
        public string $statut,
        public ?string $commentaire = null
    ) {}

    /**
     * Get the notification's delivery channels.
     * Both database (in-app) and mail (email).
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
        $label = $this->commande->statutLabel();
        $message = (new MailMessage)
            ->subject("[RETISS] Mise à jour de votre commande {$this->commande->numero} : {$label}")
            ->greeting("Bonjour " . ($notifiable->full_name ?: $notifiable->name) . ",")
            ->line("Le statut de votre commande n° {$this->commande->numero} a évolué vers : **{$label}**.")
            ->line("Montant total : **" . number_format($this->commande->montant_total, 2) . " DT**.");

        if ($this->commentaire) {
            $message->line("Commentaire du service logistique : « {$this->commentaire} »");
        }

        if ($this->commande->date_livraison_estimee && !in_array($this->commande->statut, ['LIVREE', 'ANNULEE'])) {
            $message->line("Date de livraison estimée : " . $this->commande->date_livraison_estimee->format('d/m/Y'));
        }

        $message->action('Suivre ma commande en direct', route('commandes.show', $this->commande))
            ->line("Merci de faire confiance à RETISS pour un textile plus durable et solidaire ! 🌿");

        return $message;
    }

    /**
     * In-app database notification.
     */
    public function toArray(object $notifiable): array
    {
        return [
            'commande_id'  => $this->commande->id,
            'numero'       => $this->commande->numero,
            'statut'       => $this->statut,
            'statut_label' => $this->commande->statutLabel(),
            'montant'      => $this->commande->montant_total,
            'commentaire'  => $this->commentaire,
            'message'      => "Le statut de votre commande {$this->commande->numero} est désormais : {$this->commande->statutLabel()}.",
            'url'          => route('commandes.show', $this->commande),
        ];
    }
}
