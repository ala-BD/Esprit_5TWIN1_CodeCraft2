<?php

namespace App\Services\Upcycling;

use App\Models\Atelier;
use App\Models\MessageProjet;
use App\Models\ProjetUpcycling;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * IA M3 — Assistant styliste conversationnel d'un projet.
 *
 * Il connaît tout le projet (vêtement, défauts, idées, idée retenue, devis, étape)
 * et l'historique de la discussion entre le client et l'atelier.
 */
class AssistantStylisteService
{
    /** Nombre de messages d'historique transmis à l'IA */
    const HISTORIQUE = 12;

    public function __construct(private GeminiClient $gemini) {}

    public function repondre(ProjetUpcycling $projet, string $question): string
    {
        if ($this->gemini->estConfigure()) {
            try {
                return $this->gemini->genererTexte($this->instruction($projet), $this->tours($projet, $question));
            } catch (Throwable $e) {
                Log::warning('Upcycling IA : assistant styliste indisponible.', ['erreur' => $e->getMessage()]);
            }
        }

        return $this->reponseLocale($projet);
    }

    /** Consignes + fiche complète du projet */
    private function instruction(ProjetUpcycling $projet): string
    {
        $idee = $projet->idee_retenue;
        $lignes = [
            "Tu es le styliste IA de TextileCycle, plateforme tunisienne d'upcycling textile.",
            "Tu conseilles le client et l'atelier de couture sur ce projet : choix de tissus, couleurs, finitions, étapes, entretien, coûts.",
            'Réponds en français correct (avec les accents), de façon concrète et bienveillante, en 120 mots maximum.',
            "N'utilise pas de titres markdown ; des tirets suffisent pour une liste. Ne promets jamais de prix ou de délai à la place de l'atelier.",
            '',
            'FICHE DU PROJET',
            "- Vêtement : {$projet->type_vetement} en {$projet->matiere}" . ($projet->couleur ? ", couleur {$projet->couleur}" : ''),
            '- État : ' . (ProjetUpcycling::ETATS[$projet->etat] ?? $projet->etat),
            '- Défauts repérés : ' . (implode(', ', $projet->analyse_ia['defauts'] ?? []) ?: 'aucun'),
            "- Description du client : {$projet->description}",
            '- Budget : ' . ($projet->budget_max ? $projet->budget_max . ' DT' : 'non précisé'),
            '- Étape actuelle : ' . (ProjetUpcycling::ETAPES[$projet->statut]['label'] ?? $projet->statut),
        ];

        if ($idee) {
            $lignes[] = "- Idée retenue : {$idee['titre']} ({$idee['description']})";
            $lignes[] = '- Étapes prévues : ' . implode(' ; ', $idee['etapes'] ?? []);
            $lignes[] = '- Matériaux : ' . implode(', ', $idee['materiaux'] ?? []);
        } else {
            $lignes[] = '- Idées proposées (aucune encore choisie) : ' . collect($projet->idee_generee_ia ?? [])->pluck('titre')->implode(', ');
        }

        if ($projet->atelier) {
            $lignes[] = "- Atelier : {$projet->atelier->nom}, spécialité " . (Atelier::SPECIALITES[$projet->atelier->specialite] ?? $projet->atelier->specialite);
        }
        if ($devis = $projet->devis_accepte) {
            $lignes[] = "- Devis accepté : {$devis->montant} DT, {$devis->delai_jours} jours";
        }

        return implode("\n", $lignes);
    }

    /**
     * Historique de la discussion au format Gemini (rôles alternés user / model).
     */
    private function tours(ProjetUpcycling $projet, string $question): array
    {
        $messages = $projet->messages()->latest('id')->take(self::HISTORIQUE)->get()->reverse();

        $tours = [];
        foreach ($messages as $message) {
            $role  = $message->auteur === MessageProjet::AUTEUR_IA ? 'model' : 'user';
            $texte = $message->auteur === MessageProjet::AUTEUR_IA
                ? $message->contenu
                : '[' . ($message->auteur === MessageProjet::AUTEUR_ATELIER ? 'Atelier' : 'Client') . '] ' . $message->contenu;
            $this->ajouterTour($tours, $role, $texte);
        }

        // La question vient d'être enregistrée : on s'assure qu'elle termine bien l'échange
        if (empty($tours) || end($tours)['role'] !== 'user') {
            $this->ajouterTour($tours, 'user', $question);
        }

        // Gemini exige que la conversation commence par l'utilisateur
        while ($tours && $tours[0]['role'] !== 'user') {
            array_shift($tours);
        }

        return $tours;
    }

    /** Fusionne les messages consécutifs d'un même rôle */
    private function ajouterTour(array &$tours, string $role, string $texte): void
    {
        $dernier = array_key_last($tours);
        if ($dernier !== null && $tours[$dernier]['role'] === $role) {
            $tours[$dernier]['texte'] .= "\n" . $texte;
        } else {
            $tours[] = ['role' => $role, 'texte' => $texte];
        }
    }

    /** Réponse de secours quand l'IA n'est pas disponible */
    private function reponseLocale(ProjetUpcycling $projet): string
    {
        $idee = $projet->idee_retenue;
        if (!$idee) {
            return "Je ne suis pas joignable pour le moment. En attendant, comparez les idées proposées : "
                . collect($projet->idee_generee_ia ?? [])->pluck('titre')->implode(', ')
                . ". Choisissez celle qui correspond à votre usage quotidien.";
        }

        return "Je ne suis pas joignable pour le moment. Rappel de votre projet « {$idee['titre']} » :\n- "
            . implode("\n- ", array_slice($idee['etapes'] ?? [], 0, 4))
            . "\nMatériaux à prévoir : " . (implode(', ', $idee['materiaux'] ?? []) ?: 'aucun') . '.';
    }
}
