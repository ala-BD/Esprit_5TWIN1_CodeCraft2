<?php

namespace App\Services\Assistant;

use App\Models\User;
use Closure;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Transforme une commande dictée en actions du catalogue, via un modèle de langage.
 *
 * Le modèle ne fait que proposer : chaque action passe par $preparer (lecture seule).
 * Si la préparation échoue (cible introuvable, donnée invalide…), l'erreur est rendue
 * au modèle, qui peut se corriger avec les données dont il dispose ou poser une question.
 */
class Interpreteur
{
    /** Nombre maximal d'allers-retours avec le modèle pour une même commande */
    private const TOURS_MAX = 2;

    /** Commande interne envoyée par le navigateur pour poursuivre une demande en plusieurs étapes */
    public const SUITE = '[suite]';

    /** Réponse du modèle quand il ne reste rien à faire */
    private const RIEN = 'NOTHING_LEFT';

    /** Erreurs du service d'IA : [français, anglais] */
    private const ERREURS = [
        'reseau'  => ["Impossible de joindre le service d'IA. Vérifiez la connexion internet.", 'I cannot reach the AI service. Check the internet connection.'],
        'cle'     => ["La clé d'API de l'assistant est refusée. Vérifiez ASSISTANT_API_KEY dans le fichier .env.", 'The assistant API key was rejected. Check ASSISTANT_API_KEY in the .env file.'],
        'quota'   => ["Le quota gratuit du service d'IA est atteint pour le moment. Réessayez dans une minute.", 'The free quota of the AI service is used up for now. Try again in a minute.'],
        'service' => ["Le service d'IA a renvoyé une erreur. Réessayez dans un instant.", 'The AI service returned an error. Try again in a moment.'],
    ];

    /** Message lisible pour une erreur du service d'IA */
    public static function erreur(string $code, string $langue): string
    {
        return self::ERREURS[$code][$langue === 'en' ? 1 : 0];
    }

    public function estConfigure(): bool
    {
        return filled(config('assistant.api_key'));
    }

    /**
     * @param  array<int, array{role: string, content: string}>  $historique  échanges précédents de la conversation
     * @param  Closure(string, array): array  $preparer  prépare une action et renvoie sa réponse (navigation, confirmation ou message d'échec)
     * @return array réponse prête pour le navigateur ; « suite » contient les actions suivantes d'une commande multiple
     */
    public function interpreter(User $user, string $texte, string $langue, array $historique, ?string $page, Carbon $aujourdhui, Closure $preparer): array
    {
        // Reprise automatique d'une demande en plusieurs étapes, après une action confirmée
        if ($texte === self::SUITE) {
            $texte = 'Continue with what remains of my previous request, using the data above (it is up to date). '
                . 'If nothing remains, reply with exactly: ' . self::RIEN;
        }

        $messages = [
            ['role' => 'system', 'content' => $this->consigne($user, $langue, $page, $aujourdhui)],
            ...$historique,
            ['role' => 'user', 'content' => $texte],
        ];

        $dernierEchec = null;

        for ($tour = 1; $tour <= self::TOURS_MAX; $tour++) {
            $reponse = $this->appeler($user, $messages);

            if (is_string($reponse)) {
                return $this->message(self::erreur($reponse, $langue), $langue);
            }

            // Quota par minute atteint : le navigateur patiente puis renvoie la commande tout seul
            if (is_int($reponse)) {
                return [...$this->message(self::erreur('quota', $langue), $langue), 'reessayer' => $reponse];
            }

            $choix  = $reponse->json('choices.0.message', []);
            $appels = $choix['tool_calls'] ?? [];

            // Réponse en texte : une question de suivi, ou une explication
            if (!$appels) {
                $texteModele = trim((string) ($choix['content'] ?? ''));

                if (str_contains($texteModele, self::RIEN)) {
                    return ['type' => 'rien'];
                }

                return $texteModele !== ''
                    ? $this->message($texteModele, $langue)
                    : ($dernierEchec ?? $this->message($langue === 'en' ? "I didn't understand. Could you rephrase?" : "Je n'ai pas compris la demande. Pouvez-vous la reformuler ?", $langue));
            }

            $actions = array_map(fn (array $appel) => [
                'action'    => (string) ($appel['function']['name'] ?? ''),
                'arguments' => is_array($arguments = json_decode($appel['function']['arguments'] ?? '{}', true)) ? $arguments : [],
            ], $appels);

            // Le drapeau « encore » est destiné au navigateur, pas à l'action elle-même
            $encore = false;
            foreach ($actions as &$action) {
                $encore = $encore || filter_var($action['arguments'][Catalogue::ENCORE] ?? false, FILTER_VALIDATE_BOOLEAN);
                unset($action['arguments'][Catalogue::ENCORE]);
            }
            unset($action);

            $resultat = $preparer($actions[0]['action'], $actions[0]['arguments']);

            if ($resultat['type'] !== 'message') {
                // Les actions suivantes seront préparées une à une, après confirmation de la précédente.
                // « encore » : une fois tout confirmé, le navigateur redemandera la suite au modèle.
                return [...$resultat, 'suite' => array_slice($actions, 1), 'encore' => $encore];
            }

            // Échec : on le rend au modèle pour qu'il se corrige ou l'explique
            $dernierEchec = $resultat;
            $messages[] = ['role' => 'assistant', 'content' => null, 'tool_calls' => [$appels[0]]];
            $messages[] = ['role' => 'tool', 'tool_call_id' => $appels[0]['id'] ?? 'appel', 'content' => 'FAILED: ' . $resultat['message']
                . ' — Fix the call using the data you were given, or ask the user ONE short question. Do not repeat the same call.'];
        }

        return $dernierEchec;
    }

    /** @return Response|string|int la réponse du service, le code d'une erreur de self::ERREURS, ou le délai (s) avant de réessayer */
    private function appeler(User $user, array $messages): Response|string|int
    {
        $corps = [
            'model'       => config('assistant.model'),
            'messages'    => $messages,
            'tools'       => Catalogue::pour($user),
            'tool_choice' => 'auto',
            'parallel_tool_calls' => true,
            'temperature' => 0,
            ...config('assistant.options', []),
        ];

        // Un appel d'outil mal formé ou une erreur passagère se règle souvent en réessayant une fois
        foreach ([1, 2] as $essai) {
            try {
                $reponse = Http::withToken(config('assistant.api_key'))
                    ->timeout(config('assistant.timeout'))
                    ->acceptJson()
                    ->post(config('assistant.base_url') . '/chat/completions', $corps);
            } catch (ConnectionException $e) {
                if ($essai === 1) {
                    continue;
                }

                return 'reseau';
            }

            if ($reponse->successful()) {
                return $reponse;
            }

            $reessayable = $reponse->serverError() || ($reponse->status() === 400 && str_contains($reponse->body(), 'tool_use_failed'));
            if ($essai === 1 && $reessayable) {
                continue;
            }

            // Le quota gratuit se compte par minute : il se libère en quelques secondes
            if ($reponse->status() === 429) {
                $attente = (int) ceil((float) ($reponse->header('retry-after') ?: 15));

                if ($attente <= 40) {
                    return max(2, $attente);
                }
            }

            Log::warning('Assistant vocal : erreur du service IA', ['statut' => $reponse->status(), 'corps' => $reponse->body()]);

            return match ($reponse->status()) {
                401, 403 => 'cle',
                429      => 'quota',
                default  => 'service',
            };
        }

        return 'service';
    }

    private function message(string $message, string $langue): array
    {
        return ['type' => 'message', 'message' => $message, 'voix' => $langue];
    }

    private function consigne(User $user, string $langue, ?string $page, Carbon $aujourdhui): string
    {
        $espace  = $user->isAdmin() ? 'the ADMIN area: managing user accounts' : 'the LOGISTICS area: the rounds (tournées) and missions of one collector';
        $reponse = $langue === 'en' ? 'ENGLISH' : 'FRENCH';
        $jour    = $aujourdhui->format('l j F Y') . ' (' . $aujourdhui->format('Y-m-d') . ')';
        $donnees = Contexte::pour($user, $page, $aujourdhui);

        $specifique = $user->isAdmin() ? <<<'TXT'
        - Roles: donor/donateur → DONATEUR, customer/client → CLIENT, collector/driver/collecteur/livreur → COLLECTEUR, workshop/atelier → ATELIER, recycler/recycleur → RECYCLEUR, admin → ADMIN. If no role is said when creating, omit it.
        - "deactivate / disable / block / suspend / désactive / bloque" → actif=false. "activate / enable / réactive" → actif=true.
        - To target an existing user, find them in the USER DIRECTORY and pass their `utilisateur_id`. Speech recognition mangles names ("Kareem Mansoori" is Karim Mansouri): pick the closest directory entry. Only if two entries fit equally well, ask which one, naming both.
        - Creating a user needs first name, last name and email. If the email is missing, ask for it. Never ask for a phone, a role or a password.
        TXT : <<<'TXT'
        - Mission type: pickup/collect/collection/collecte/ramassage/récupérer → COLLECTE. delivery/deliver/drop-off/livraison/livrer → LIVRAISON.
        - Mission status: to do/à faire → A_FAIRE, in progress/started/en cours → EN_COURS, done/finished/completed/terminée/faite → TERMINEE, failed/missed/absent/échouée → ECHOUEE.
        - Round status: planned → PLANIFIEE, in progress/started → EN_COURS, done/finished → TERMINEE, cancelled → ANNULEE.
        - To target an existing round, find it in ROUNDS and pass its `tournee_id`. Missions are targeted by their stop number (`ordre`) inside that round.
        - Which round, when the user does not name one: the round ON SCREEN if there is one; otherwise the round marked TODAY; otherwise, if exactly one round exists, that one. Only if none of these applies, ask which round, naming the candidates by zone and date.
        - A zone or date that matches no round in ROUNDS means that round does not exist: say so and offer to create it. Do not call a tool with it.
        - Adding a mission needs a type, an address and a time. The round's zone is NOT a field of a mission: never ask for a zone when adding a mission. Never ask for a stop number or a status; "make it last" / "at the end" means leave `ordre` empty.
        - Creating a round needs a date, a zone and a vehicle. If the vehicle is missing and earlier rounds used one, reuse the most recent vehicle.
        - "Mark mission 2 as done", "stop 3 failed, customer absent" → modifier_mission with statut (and preuve_livraison for the reason or proof).
        TXT;

        return <<<TXT
        You are the voice assistant of RETISS, a textile circular-economy platform. You operate {$espace}.
        You do two things: you turn what the user SAYS into actions by calling tools, and you ANSWER QUESTIONS about the data below.
        You are decisive: act or answer whenever you reasonably can.

        Today is {$jour}. Convert relative dates ("tomorrow", "demain", "next Monday") to YYYY-MM-DD yourself.

        {$donnees}

        ANSWERING QUESTIONS
        - When the user asks about the data ("what is going on?", "how many recyclers?", "who joined last?", "how is my day going?", "fais-moi un résumé"), ANSWER from the data above. Do not call a tool and do not just open a page: they want to be told.
        - Analyse, do not recite: give the key numbers, then what they mean (a trend, something unusual, what needs attention), like a colleague giving a quick briefing. Count, compare and compute percentages yourself.
        - Use only the data above. If it does not contain the answer, say what is missing in one sentence.
        - When the data gives only a count (for example how many emails are verified), give the count: never guess which person or item it refers to.
        - Be exact about dates: only a round marked TODAY is today's. If nothing is planned today, say so, then mention the nearest round and its date.
        - Open a page only when the user asks to see, show or open it.

        HOW TO DECIDE
        1. The text comes from speech recognition, so expect misheard words, missing punctuation and numbers written oddly. Infer the intended meaning; never complain about the wording.
        2. Use the data above and the conversation so far to fill in everything you can. "him", "that one", "the same", "it" refer to the last thing discussed or to what is on screen.
        3. If every REQUIRED parameter of a tool is known or can be inferred, CALL THE TOOL NOW. Optional parameters are optional: leave them out, never ask for them.
        4. If the user asks for several things at once ("add X and Y", "create a round and add two pickups"), emit SEVERAL tool calls in the same reply, one per thing, in order. Do not stop after the first.
           When an action can only be done after an earlier one is confirmed (a mission for a round that does not exist yet), call only the first tool and set its `encore` to true.
           In the conversation, an assistant line starting with "PROPOSED" is an action still waiting for the user's yes/no: if the user now adds or corrects something about it, call the tool again with the earlier values AND the new ones merged. A line starting with "DONE" is finished: never redo it.
        5. Ask a question only when a required value is truly unknown. Ask for ALL missing values in ONE short sentence, in plain words. Then use the answer together with what was already said: never ask again for something already given.
        6. Never invent an email, phone number, address, date or name. Use only what was said or what is in the data.
        7. If a tool result says FAILED, fix the call with the data you have, or explain the problem in one sentence.
        8. If the request has nothing to do with this area or its data, say so in one sentence and mention one thing you can do.

        VALUES
        - Dictated email: lowercase, no spaces; "at" / "arobase" → @, "dot" / "point" → a dot, "underscore" / "tiret bas" → _, "dash" / "tiret" → -. Spell every part exactly as said: never translate or "correct" it ("exemple" stays "exemple", it does not become "example").
        - Phone: digits only.
        - Time: 24-hour HH:MM ("half past nine" → 09:30, "2 pm" → 14:00, "midi" → 12:00).
        {$specifique}

        HOW TO SPEAK
        - Reply in {$reponse}, whatever language the user or the data is in. Plain text that sounds natural read aloud: no lists, no markdown, no symbols.
        - A question or a remark about an action: one or two short sentences. An answer about the data: FOUR sentences at most, in a single paragraph with no line breaks, no dashes and no bullet points.
        - Never say an internal number: no "#2", no "round 2" for a round. Name a round by its zone and date, a user by their name.
        - Say roles and statuses in plain words in the reply language ("recyclers", "in progress", "collecteurs", "terminée").
        - Never show internal codes or parameter names (COLLECTE, A_FAIRE, tournee_id, HH:MM…). Say "pickup or delivery", "the time", "the round".
        - Do not announce what you are about to do and do not ask for confirmation: the app shows a confirmation card itself.
        TXT;
    }
}
