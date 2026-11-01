<?php

namespace App\Services\Upcycling;

use App\Exceptions\RegleMetierException;
use App\Models\Atelier;
use App\Models\Devis;
use App\Models\DonVetement;
use App\Models\MessageProjet;
use App\Models\ProjetUpcycling;
use App\Models\User;
use App\Notifications\UpcyclingNotification;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Règles métier du cycle de vie d'un projet d'upcycling.
 *
 * Utilisé à la fois par les contrôleurs web et par l'API REST :
 * les contrôleurs ne font plus que valider, autoriser et répondre.
 * Les autorisations sont vérifiées en amont (ProjetUpcyclingPolicy) ;
 * ici on vérifie les règles d'état (RegleMetierException).
 */
class GestionProjetService
{
    public function __construct(
        private IdeeUpcyclingService $ideeService,
        private AssistantStylisteService $assistant,
        private ControleQualiteService $controleQualite,
    ) {}

    /*
    |------------------------------------------------------------------
    | Demande
    |------------------------------------------------------------------
    */

    /** Crée la demande et génère les idées de l'IA */
    public function creer(User $client, array $data, ?UploadedFile $photo = null): ProjetUpcycling
    {
        unset($data['photo']);
        if ($photo) {
            $data['photo'] = $photo->store(ProjetUpcycling::DOSSIER_PHOTOS, 'public');
        }

        $ia = $this->ideeService->generer($data, self::cheminPhoto($data['photo'] ?? null));

        return DB::transaction(function () use ($client, $data, $ia) {
            $projet = ProjetUpcycling::create($data + $this->ideeService->impact($data['type_vetement']) + [
                'client_id'       => $client->id,
                'idee_generee_ia' => $ia['idees'],
                'source_ia'       => $ia['source'],
                'analyse_ia'      => ['defauts' => $ia['defauts']],
                'statut'          => ProjetUpcycling::STATUT_DEMANDE,
            ]);

            if ($projet->don_vetement_id) {
                DonVetement::whereKey($projet->don_vetement_id)->update(['statut' => 'UPCYCLING']);
            }

            return $projet;
        });
    }

    /**
     * Modifie la demande ; si le vêtement change, l'IA régénère les idées.
     *
     * @return bool true si les idées ont été régénérées
     */
    public function modifier(ProjetUpcycling $projet, array $data, ?UploadedFile $photo = null): bool
    {
        $this->exigerModifiable($projet);
        unset($data['photo'], $data['don_vetement_id']);

        if ($photo) {
            ProjetUpcycling::supprimerPhoto($projet->photo);
            $data['photo'] = $photo->store(ProjetUpcycling::DOSSIER_PHOTOS, 'public');
        }

        $data += $projet->only(['type_vetement', 'matiere', 'etat']);
        $vetementModifie = $photo !== null
            || $data['type_vetement'] !== $projet->type_vetement
            || $data['matiere'] !== $projet->matiere
            || $data['etat'] !== $projet->etat;

        if ($vetementModifie) {
            $ia = $this->ideeService->generer($data + $projet->only(['couleur', 'description', 'budget_max']),
                self::cheminPhoto($data['photo'] ?? $projet->photo));
            $data += $this->ideeService->impact($data['type_vetement']) + [
                'idee_generee_ia'   => $ia['idees'],
                'source_ia'         => $ia['source'],
                'analyse_ia'        => ['defauts' => $ia['defauts']],
                'produit_final'     => null,
                'categorie_produit' => null,
                'prix_estime_min'   => null,
                'prix_estime_max'   => null,
            ];
        }

        $projet->update($data);

        return $vetementModifie;
    }

    public function supprimer(ProjetUpcycling $projet): void
    {
        if (!in_array($projet->statut, [ProjetUpcycling::STATUT_DEMANDE, ProjetUpcycling::STATUT_ANNULE], true)) {
            throw new RegleMetierException('Seule une demande sans atelier ou annulée peut être supprimée.');
        }

        $this->libererDon($projet);
        ProjetUpcycling::supprimerPhoto($projet->photo);
        ProjetUpcycling::supprimerPhoto($projet->photo_resultat);
        $projet->delete();
    }

    /*
    |------------------------------------------------------------------
    | Idées IA et choix de l'atelier
    |------------------------------------------------------------------
    */

    public function regenererIdees(ProjetUpcycling $projet, ?string $consigne = null): void
    {
        $this->exigerModifiable($projet);

        $ia = $this->ideeService->generer(
            $projet->only(['type_vetement', 'matiere', 'etat', 'couleur', 'description', 'budget_max']),
            self::cheminPhoto($projet->photo),
            $consigne
        );

        $projet->update([
            'idee_generee_ia' => $ia['idees'],
            'source_ia'       => $ia['source'],
            'analyse_ia'      => ['defauts' => $ia['defauts']],
        ]);
    }

    /** @return array l'idée retenue */
    public function choisirIdee(ProjetUpcycling $projet, int $index): array
    {
        $this->exigerModifiable($projet);

        $idee = ($projet->idee_generee_ia ?? [])[$index] ?? null;
        if (!$idee) {
            throw new RegleMetierException("Cette idée n'existe pas.");
        }

        $projet->update([
            'produit_final'     => $idee['titre'],
            'categorie_produit' => $idee['categorie'],
            'prix_estime_min'   => $idee['prix_min'] ?? null,
            'prix_estime_max'   => $idee['prix_max'] ?? null,
        ]);

        return $idee;
    }

    public function choisirAtelier(ProjetUpcycling $projet, Atelier $atelier): void
    {
        $this->exigerModifiable($projet);
        if (!$projet->produit_final) {
            throw new RegleMetierException("Choisissez d'abord une idée de transformation.");
        }
        if (!$atelier->actif) {
            throw new RegleMetierException('Cet atelier est indisponible.');
        }

        DB::transaction(function () use ($projet, $atelier) {
            // Changement d'atelier : le devis en attente de l'ancien atelier tombe
            $projet->devis()->where('statut', Devis::STATUT_EN_ATTENTE)->update(['statut' => Devis::STATUT_REFUSE]);
            $projet->update(['atelier_id' => $atelier->id, 'statut' => ProjetUpcycling::STATUT_ATELIER_CHOISI]);
        });

        $atelier->user->notify(new UpcyclingNotification(
            $projet,
            "Nouvelle demande de devis : « {$projet->produit_final} » ({$projet->type_vetement}) de {$projet->client->full_name}.",
            'fa-file-invoice-dollar'
        ));
    }

    /*
    |------------------------------------------------------------------
    | Devis
    |------------------------------------------------------------------
    */

    public function envoyerDevis(ProjetUpcycling $projet, array $data): Devis
    {
        if ($projet->statut !== ProjetUpcycling::STATUT_ATELIER_CHOISI) {
            throw new RegleMetierException("Ce projet n'attend pas de devis.");
        }
        if ($projet->devis()->where('statut', Devis::STATUT_EN_ATTENTE)->exists()) {
            throw new RegleMetierException('Un devis est déjà en attente de réponse du client.');
        }

        $devis = $projet->devis()->create([
            'montant'       => $data['montant'],
            'delai_jours'   => $data['delai_jours'],
            'message'       => $data['message'] ?? null,
            'statut'        => Devis::STATUT_EN_ATTENTE,
            'date_emission' => now(),
        ]);

        $projet->client->notify(new UpcyclingNotification(
            $projet,
            "{$projet->atelier->nom} vous propose un devis de " . number_format($devis->montant, 0, ',', ' ')
                . " DT ({$devis->delai_jours} jours) pour « {$projet->produit_final} ».",
            'fa-file-invoice-dollar'
        ));

        return $devis;
    }

    public function accepterDevis(Devis $devis): void
    {
        $this->exigerDevisEnAttente($devis);
        $projet = $devis->projetUpcycling;

        DB::transaction(function () use ($devis, $projet) {
            $devis->update(['statut' => Devis::STATUT_ACCEPTE]);
            $projet->update(['statut' => ProjetUpcycling::STATUT_DEVIS_ACCEPTE, 'date_debut' => now()]);
        });

        $projet->atelier->user->notify(new UpcyclingNotification(
            $projet,
            "{$projet->client->full_name} a accepté votre devis pour « {$projet->produit_final} ». Les travaux peuvent commencer !",
            'fa-handshake'
        ));
    }

    public function refuserDevis(Devis $devis): void
    {
        $this->exigerDevisEnAttente($devis);
        $projet = $devis->projetUpcycling;

        $devis->update(['statut' => Devis::STATUT_REFUSE]);

        $projet->atelier->user->notify(new UpcyclingNotification(
            $projet,
            "{$projet->client->full_name} a refusé votre devis de " . number_format($devis->montant, 0, ',', ' ')
                . ' DT. Vous pouvez en proposer un nouveau.',
            'fa-file-circle-xmark'
        ));
    }

    /*
    |------------------------------------------------------------------
    | Suivi des travaux
    |------------------------------------------------------------------
    */

    /** @return string la nouvelle étape */
    public function avancer(ProjetUpcycling $projet, ?UploadedFile $photoResultat = null): string
    {
        $suivante = $projet->etape_suivante;
        if ($suivante === null) {
            throw new RegleMetierException('Aucune étape suivante pour ce projet.');
        }

        $data = ['statut' => $suivante];
        if ($suivante === ProjetUpcycling::STATUT_TERMINE) {
            $data['date_fin'] = now();
            if ($photoResultat) {
                ProjetUpcycling::supprimerPhoto($projet->photo_resultat);
                $data['photo_resultat'] = $photoResultat->store(ProjetUpcycling::DOSSIER_PHOTOS, 'public');
            }
        }
        $projet->update($data);

        // Fin des travaux : l'IA compare le produit fini au vêtement d'origine
        if ($suivante === ProjetUpcycling::STATUT_TERMINE && $projet->photo_resultat) {
            if ($controle = $this->controleQualite->verifier($projet)) {
                $projet->update(['controle_qualite_ia' => $controle]);
            }
        }

        $label = ProjetUpcycling::ETAPES[$suivante]['label'];
        $projet->client->notify(new UpcyclingNotification(
            $projet,
            $suivante === ProjetUpcycling::STATUT_TERMINE
                ? "Votre « {$projet->produit_final} » est terminé ! Découvrez le résultat et laissez un avis."
                : "Votre projet « {$projet->produit_final} » avance : étape « {$label} ».",
            $suivante === ProjetUpcycling::STATUT_TERMINE ? 'fa-gift' : ProjetUpcycling::ETAPES[$suivante]['icon']
        ));

        return $suivante;
    }

    public function annuler(ProjetUpcycling $projet): void
    {
        if (!$projet->estAnnulable()) {
            throw new RegleMetierException('Les travaux ont commencé : le projet ne peut plus être annulé.');
        }

        DB::transaction(function () use ($projet) {
            $projet->devis()->where('statut', Devis::STATUT_EN_ATTENTE)->update(['statut' => Devis::STATUT_REFUSE]);
            $this->libererDon($projet);
            $projet->update(['statut' => ProjetUpcycling::STATUT_ANNULE]);
        });
    }

    public function noter(ProjetUpcycling $projet, int $note, ?string $commentaire = null): void
    {
        if ($projet->statut !== ProjetUpcycling::STATUT_TERMINE) {
            throw new RegleMetierException('Le projet doit être terminé pour être noté.');
        }
        if ($projet->note_client !== null) {
            throw new RegleMetierException('Vous avez déjà noté ce projet.');
        }

        $projet->update(['note_client' => $note, 'commentaire_client' => $commentaire]);
        $projet->atelier?->recalculerNote();

        $projet->atelier?->user->notify(new UpcyclingNotification(
            $projet,
            "{$projet->client->full_name} a noté « {$projet->produit_final} » : {$note}/5.",
            'fa-star'
        ));
    }

    /*
    |------------------------------------------------------------------
    | Discussion
    |------------------------------------------------------------------
    */

    /**
     * Ajoute un message au fil ; avec $demanderIa, le styliste IA répond.
     *
     * @return Collection<int, MessageProjet> les messages créés (1 ou 2)
     */
    public function ecrireMessage(ProjetUpcycling $projet, User $auteur, string $contenu, bool $demanderIa = false): Collection
    {
        if ($projet->statut === ProjetUpcycling::STATUT_ANNULE) {
            throw new RegleMetierException('Ce projet est annulé.');
        }

        $estClient = $projet->client_id === $auteur->id;
        $messages = collect([$projet->messages()->create([
            'user_id' => $auteur->id,
            'auteur'  => $estClient ? MessageProjet::AUTEUR_CLIENT : MessageProjet::AUTEUR_ATELIER,
            'contenu' => trim($contenu),
        ])]);

        if ($demanderIa) {
            $messages->push($projet->messages()->create([
                'user_id' => null,
                'auteur'  => MessageProjet::AUTEUR_IA,
                'contenu' => $this->assistant->repondre($projet, trim($contenu)),
            ]));
        } else {
            $destinataire = $estClient ? $projet->atelier?->user : $projet->client;
            $expediteur   = $estClient ? $projet->client->full_name : $projet->atelier?->nom;
            $destinataire?->notify(new UpcyclingNotification(
                $projet,
                "{$expediteur} : « " . Str::limit(trim($contenu), 70) . ' »',
                'fa-comment-dots'
            ));
        }

        return $messages;
    }

    /*
    |------------------------------------------------------------------
    | Helpers
    |------------------------------------------------------------------
    */

    /** Chemin absolu d'une photo (démo dans public/, envois dans storage) */
    public static function cheminPhoto(?string $photo): ?string
    {
        if (!$photo) return null;

        return str_starts_with($photo, 'images/') ? public_path($photo) : Storage::disk('public')->path($photo);
    }

    private function exigerModifiable(ProjetUpcycling $projet): void
    {
        if (!$projet->estModifiable()) {
            throw new RegleMetierException('Ce projet ne peut plus être modifié : les travaux ont commencé.');
        }
    }

    private function exigerDevisEnAttente(Devis $devis): void
    {
        if ($devis->statut !== Devis::STATUT_EN_ATTENTE) {
            throw new RegleMetierException('Ce devis a déjà reçu une réponse.');
        }
    }

    /** Rend le don au circuit de tri quand le projet est abandonné */
    private function libererDon(ProjetUpcycling $projet): void
    {
        if ($projet->don_vetement_id) {
            DonVetement::whereKey($projet->don_vetement_id)->update(['statut' => 'EN_TRI']);
            $projet->update(['don_vetement_id' => null]);
        }
    }
}
