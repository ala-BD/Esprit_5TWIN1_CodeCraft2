<?php

namespace App\Http\Controllers\Upcycling;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\TokenRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Page « Accès API » : tokens personnels Sanctum et documentation de l'API REST v1.
 */
class AccesApiController extends Controller
{
    /** Durées de validité proposées (en jours ; null = sans expiration) */
    const EXPIRATIONS = ['7' => '7 jours', '30' => '30 jours', '90' => '90 jours'];

    public function index(): View
    {
        return view('upcycling.api.index', [
            'tokens'      => Auth::user()->tokens()->latest()->get(),
            'abilities'   => TokenRequest::ABILITIES,
            'expirations' => self::EXPIRATIONS,
            'endpoints'   => $this->endpoints(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'nom'         => ['required', 'string', 'max:60'],
            'abilities'   => ['required', 'array', 'min:1'],
            'abilities.*' => [Rule::in(array_keys(TokenRequest::ABILITIES))],
            'expiration'  => ['required', Rule::in(array_keys(self::EXPIRATIONS))],
        ], [
            'nom.required'       => 'Donnez un nom au token (ex. Postman, application mobile).',
            'abilities.required' => 'Choisissez au moins une permission.',
            'expiration.in'      => 'Durée de validité invalide.',
        ]);

        $token = Auth::user()->createToken(
            $request->nom,
            $request->abilities,
            now()->addDays((int) $request->expiration)
        );

        // Le token en clair n'est affiché qu'une seule fois
        return redirect()->route('upcycling.api.index')
            ->with('nouveau_token', $token->plainTextToken)
            ->with('success', "Token « {$request->nom} » créé. Copiez-le maintenant : il ne sera plus affiché.");
    }

    public function destroy(int $token): RedirectResponse
    {
        $supprimes = Auth::user()->tokens()->whereKey($token)->delete();
        abort_if($supprimes === 0, 404);

        return back()->with('success', 'Token révoqué : il ne permet plus aucun accès.');
    }

    /** Endpoints documentés : [méthode, chemin, permission, description] */
    private function endpoints(): array
    {
        return [
            'Authentification' => [
                ['POST',   '/auth/token', null, 'Obtenir un token (email, password, nom_appareil, abilities[])'],
                ['GET',    '/auth/moi', null, 'Utilisateur et permissions du token'],
                ['DELETE', '/auth/token', null, 'Révoquer le token utilisé'],
            ],
            'Ateliers' => [
                ['GET', '/ateliers?specialite=SAC&q=tunis', 'upcycling:lire', 'Catalogue paginé et filtrable'],
                ['GET', '/ateliers/{id}', 'upcycling:lire', "Détail d'un atelier"],
            ],
            'Projets' => [
                ['GET',    '/projets?statut=TERMINE', 'upcycling:lire', 'Mes projets (client) ou projets reçus (atelier)'],
                ['POST',   '/projets', 'upcycling:ecrire', 'Nouvelle demande (multipart pour la photo) : l\'IA génère 3 idées'],
                ['GET',    '/projets/{id}', 'upcycling:lire', 'Détail : idées, devis, impact, contrôle qualité'],
                ['PATCH',  '/projets/{id}', 'upcycling:ecrire', 'Mise à jour partielle de la demande'],
                ['DELETE', '/projets/{id}', 'upcycling:ecrire', 'Supprimer une demande'],
                ['PATCH',  '/projets/{id}/idee', 'upcycling:ecrire', 'Retenir une idée : {"index": 0}'],
                ['GET',    '/projets/{id}/matching', 'upcycling:lire', 'Ateliers classés (score sur 100 et détail)'],
                ['PATCH',  '/projets/{id}/atelier', 'upcycling:ecrire', 'Choisir l\'atelier : {"atelier_id": 3}'],
                ['POST',   '/projets/{id}/avancer', 'upcycling:ecrire', 'Étape suivante (atelier), photo_resultat à la fin'],
                ['POST',   '/projets/{id}/annuler', 'upcycling:ecrire', 'Annuler avant le début des travaux'],
                ['POST',   '/projets/{id}/noter', 'upcycling:ecrire', 'Noter l\'atelier : {"note_client": 5}'],
            ],
            'Devis' => [
                ['GET',  '/projets/{id}/devis', 'upcycling:lire', 'Devis du projet'],
                ['POST', '/projets/{id}/devis', 'upcycling:ecrire', 'Proposer un devis (atelier) : montant, delai_jours'],
                ['POST', '/devis/{id}/acceptation', 'upcycling:ecrire', 'Accepter un devis (client)'],
                ['POST', '/devis/{id}/refus', 'upcycling:ecrire', 'Refuser un devis (client)'],
            ],
            'Discussion et IA' => [
                ['GET',  '/projets/{id}/messages', 'upcycling:lire', 'Fil de discussion'],
                ['POST', '/projets/{id}/messages', 'upcycling:ecrire', 'Message à l\'autre partie : {"contenu": "..."}'],
                ['POST', '/projets/{id}/styliste', 'upcycling:ia', 'Question au styliste IA (réponse incluse)'],
                ['POST', '/projets/{id}/idees', 'upcycling:ia', 'Nouvelles idées IA : {"consigne": "pour un enfant"}'],
                ['POST', '/analyse-photo', 'upcycling:ia', 'Reconnaissance du vêtement sur une photo (multipart)'],
            ],
        ];
    }
}
