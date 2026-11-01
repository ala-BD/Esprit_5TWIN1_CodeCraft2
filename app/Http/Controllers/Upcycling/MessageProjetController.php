<?php

namespace App\Http\Controllers\Upcycling;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Upcycling\Concerns\AccesUpcycling;
use App\Models\MessageProjet;
use App\Models\ProjetUpcycling;
use App\Notifications\UpcyclingNotification;
use App\Services\Upcycling\AssistantStylisteService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class MessageProjetController extends Controller
{
    use AccesUpcycling;

    public function __construct(private AssistantStylisteService $assistant) {}

    /*
    |------------------------------------------------------------------
    | POST /upcycling/projets/{projet}/messages
    | Message au fil du projet ; avec "ia=1", le styliste IA répond.
    |------------------------------------------------------------------
    */
    public function store(Request $request, ProjetUpcycling $projet): RedirectResponse
    {
        $estClient  = $this->estProprietaire($projet);
        $estAtelier = $this->estAtelierDuProjet($projet);
        abort_unless($estClient || $estAtelier, 403, 'Seuls le client et l\'atelier du projet peuvent écrire ici.');
        abort_if($projet->statut === ProjetUpcycling::STATUT_ANNULE, 422, 'Ce projet est annulé.');

        $request->validate([
            'contenu' => ['required', 'string', 'min:2', 'max:1000'],
        ], [
            'contenu.required' => 'Écrivez votre message.',
            'contenu.min'      => 'Le message est trop court.',
            'contenu.max'      => 'Le message ne doit pas dépasser 1000 caractères.',
        ]);

        $message = $projet->messages()->create([
            'user_id' => Auth::id(),
            'auteur'  => $estClient ? MessageProjet::AUTEUR_CLIENT : MessageProjet::AUTEUR_ATELIER,
            'contenu' => trim($request->contenu),
        ]);

        if ($request->boolean('ia')) {
            $projet->messages()->create([
                'user_id' => null,
                'auteur'  => MessageProjet::AUTEUR_IA,
                'contenu' => $this->assistant->repondre($projet, $message->contenu),
            ]);
        } else {
            $this->notifierAutrePartie($projet, $estClient, $message);
        }

        return redirect()->to(route('upcycling.projets.show', $projet) . '#discussion');
    }

    private function notifierAutrePartie(ProjetUpcycling $projet, bool $estClient, MessageProjet $message): void
    {
        $destinataire = $estClient ? $projet->atelier?->user : $projet->client;
        if (!$destinataire) return;

        $expediteur = $estClient ? $projet->client->full_name : $projet->atelier->nom;
        $destinataire->notify(new UpcyclingNotification(
            $projet,
            "{$expediteur} : « " . Str::limit($message->contenu, 70) . ' »',
            'fa-comment-dots'
        ));
    }
}
