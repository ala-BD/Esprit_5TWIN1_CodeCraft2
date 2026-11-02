<?php

namespace App\Services\Logistique;

use App\Models\Commande;
use App\Models\Mission;
use App\Models\Tournee;
use App\Models\User;
use App\Notifications\CommandeStatutNotification;
use App\Notifications\MissionEchoueeNotification;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Log;

/**
 * Répercute l'avancement d'une mission sur ce qu'elle transporte :
 * - livraison démarrée → commande expédiée ; livraison terminée → commande livrée ;
 *   livraison échouée → noté dans l'historique de la commande ;
 * - collecte terminée → le don passe au tri.
 * Le client est prévenu à chaque étape de sa commande.
 */
class SynchronisationMission
{
    /** Statuts de commande à partir desquels on peut expédier */
    private const AVANT_EXPEDITION = [Commande::STATUT_EN_ATTENTE, Commande::STATUT_CONFIRMEE, Commande::STATUT_EN_PREPARATION];

    public function apresChangementDeStatut(Mission $mission): void
    {
        if ($mission->type === Mission::TYPE_LIVRAISON && $mission->commande_id) {
            $this->synchroniserCommande($mission);
        }

        if ($mission->type === Mission::TYPE_COLLECTE && $mission->don_vetement_id) {
            $this->synchroniserDon($mission);
        }

        $this->avancerTournee($mission);

        if ($mission->statut === Mission::STATUT_ECHOUEE) {
            $this->alerterAdministrateurs($mission);
        }
    }

    /** Un arrêt échoué doit être replanifié : les administrateurs sont prévenus */
    private function alerterAdministrateurs(Mission $mission): void
    {
        try {
            Notification::send(User::where('role', User::ROLE_ADMIN)->where('actif', true)->get(), new MissionEchoueeNotification($mission));
        } catch (\Throwable $e) {
            Log::warning('Logistique : alerte d\'échec non envoyée', ['mission' => $mission->id, 'erreur' => $e->getMessage()]);
        }
    }

    /**
     * La tournée suit ses arrêts : elle démarre avec le premier arrêt commencé
     * et se termine quand tous ses arrêts sont faits ou échoués.
     */
    private function avancerTournee(Mission $mission): void
    {
        $tournee = $mission->tournee;

        if (!$tournee || $tournee->statut === Tournee::STATUT_ANNULEE) {
            return;
        }

        $statuts = $tournee->missions()->pluck('statut');
        $toutesCloses = $statuts->isNotEmpty() && $statuts->every(fn (string $s) => in_array($s, [Mission::STATUT_TERMINEE, Mission::STATUT_ECHOUEE], true));
        $commencee = $statuts->contains(fn (string $s) => $s !== Mission::STATUT_A_FAIRE);

        $nouveau = match (true) {
            $toutesCloses => Tournee::STATUT_TERMINEE,
            $commencee    => Tournee::STATUT_EN_COURS,
            default       => null,
        };

        // On n'avance que vers l'avant, sauf si un arrêt est rouvert sur une tournée terminée
        $rouverte = $tournee->statut === Tournee::STATUT_TERMINEE && !$toutesCloses;

        if ($nouveau && ($nouveau !== $tournee->statut) && ($tournee->statut === Tournee::STATUT_PLANIFIEE || $nouveau === Tournee::STATUT_TERMINEE || $rouverte)) {
            $tournee->update(['statut' => $nouveau]);
        }
    }

    private function synchroniserCommande(Mission $mission): void
    {
        $commande = $mission->commande;

        if (!$commande || in_array($commande->statut, [Commande::STATUT_LIVREE, Commande::STATUT_ANNULEE], true)) {
            return;
        }

        $tournee = $mission->tournee;
        $livreur = $tournee?->user?->full_name;

        match ($mission->statut) {
            Mission::STATUT_EN_COURS => in_array($commande->statut, self::AVANT_EXPEDITION, true) && $this->changer(
                $commande,
                Commande::STATUT_EXPEDIEE,
                'En cours de livraison' . ($livreur ? " par {$livreur}" : '') . '.',
                "Votre commande {$commande->numero} est en route : le livreur est en chemin vers votre adresse.",
            ),
            Mission::STATUT_TERMINEE => $this->changer(
                $commande,
                Commande::STATUT_LIVREE,
                'Livrée par le collecteur' . ($mission->preuve_livraison ? " — {$mission->preuve_livraison}" : '') . '.',
                "Votre commande {$commande->numero} a été livrée. Vous pouvez maintenant évaluer vos articles.",
            ),
            Mission::STATUT_ECHOUEE => $this->noterEchec($commande, $mission),
            default => null,
        };
    }

    private function changer(Commande $commande, string $statut, string $historique, string $message): bool
    {
        $commande->update(['statut' => $statut]);
        $commande->pushHistorique($statut, $historique);
        $this->prevenir($commande, $statut, $message);

        return true;
    }

    private function noterEchec(Commande $commande, Mission $mission): void
    {
        $raison = $mission->preuve_livraison ?: 'raison non précisée';
        $commande->pushHistorique($commande->statut, "Tentative de livraison échouée ({$raison}). Une nouvelle tentative sera planifiée.");
        $this->prevenir($commande, $commande->statut, "Nous n'avons pas pu livrer votre commande {$commande->numero} ({$raison}). Une nouvelle tentative sera planifiée.");
    }

    private function prevenir(Commande $commande, string $statut, string $message): void
    {
        try {
            $commande->user?->notify(new CommandeStatutNotification($commande, $statut, $message));
        } catch (\Throwable $e) {
            // Un e-mail qui ne part pas ne doit pas bloquer la tournée
            Log::warning('Logistique : notification de commande non envoyée', ['commande' => $commande->id, 'erreur' => $e->getMessage()]);
        }
    }

    private function synchroniserDon(Mission $mission): void
    {
        $don = $mission->donVetement;

        // Le don collecté arrive au tri ; on ne touche pas à un don déjà plus avancé
        if ($don && $mission->statut === Mission::STATUT_TERMINEE && $don->statut === 'DEPOSE') {
            $don->update(['statut' => 'EN_TRI']);
        }
    }
}
