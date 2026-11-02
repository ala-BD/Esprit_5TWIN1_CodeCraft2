<?php

namespace App\Mail;

use App\Models\Commande;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class CommandeConfirmeeMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Commande $commande)
    {
        $this->commande->loadMissing(['lignes.article.user', 'adresse', 'user']);
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "[RETISS] Commande {$this->commande->numero} — Paiement confirmé ✅",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.commande-confirmee',
            with: ['commande' => $this->commande],
        );
    }

    /**
     * PDF invoice attached to the confirmation email.
     */
    public function attachments(): array
    {
        try {
            $pdf = Pdf::loadView('commandes.invoice-pdf', ['commande' => $this->commande])
                ->setPaper('a4', 'portrait');

            return [
                Attachment::fromData(
                    fn () => $pdf->output(),
                    "facture-{$this->commande->numero}.pdf"
                )->withMime('application/pdf'),
            ];
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('[Mail] PDF attachment error: ' . $e->getMessage());
            return [];
        }
    }
}
