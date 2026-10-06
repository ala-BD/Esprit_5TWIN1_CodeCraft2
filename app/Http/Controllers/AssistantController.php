<?php

namespace App\Http\Controllers;

use App\Services\Assistant\Catalogue;
use App\Services\Assistant\Executeur;
use App\Services\Assistant\Interpreteur;
use App\Services\Assistant\Synthetiseur;
use App\Services\Assistant\Transcripteur;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;

class AssistantController extends Controller
{
    public function __construct(
        private Interpreteur $interpreteur,
        private Executeur $executeur,
        private Transcripteur $transcripteur,
        private Synthetiseur $synthetiseur,
    ) {
    }

    /*
    |------------------------------------------------------------------
    | POST /assistant/parler — Lire une phrase anglaise avec une voix naturelle
    |------------------------------------------------------------------
    | Renvoie le son. En cas d'échec, le navigateur reprend avec sa propre voix.
    */
    public function parler(Request $request): Response|JsonResponse
    {
        $donnees = $request->validate([
            'texte' => ['required', 'string', 'max:' . Synthetiseur::LONGUEUR_MAX],
        ]);

        if (!Catalogue::pour($request->user()) || !$this->synthetiseur->estConfigure()) {
            return response()->json(['erreur' => 'indisponible'], 503);
        }

        $resultat = $this->synthetiseur->lire($donnees['texte']);

        if (isset($resultat['erreur'])) {
            return response()->json(['erreur' => $resultat['erreur']], 502);
        }

        return response($resultat['audio'], 200, ['Content-Type' => $resultat['type'], 'Cache-Control' => 'no-store']);
    }

    /*
    |------------------------------------------------------------------
    | POST /assistant/transcrire — Transformer un enregistrement vocal en texte
    |------------------------------------------------------------------
    */
    public function transcrire(Request $request): JsonResponse
    {
        $donnees = $request->validate([
            'audio'  => ['required', 'file', 'max:6144'],
            'langue' => ['required', Rule::in(['fr', 'en'])],
        ]);

        $langue = $donnees['langue'];

        if (!Catalogue::pour($request->user()) || !$this->interpreteur->estConfigure()) {
            return $this->indisponible($request->user(), $langue);
        }

        $resultat = $this->transcripteur->transcrire($donnees['audio'], $langue, $request->user());

        if (isset($resultat['erreur'])) {
            return response()->json(['type' => 'message', 'message' => Interpreteur::erreur($resultat['erreur'], $langue), 'voix' => $langue]);
        }

        return response()->json(['type' => 'texte', 'texte' => $resultat['texte']]);
    }

    /*
    |------------------------------------------------------------------
    | POST /assistant/interpreter — Comprendre une commande dictée
    |------------------------------------------------------------------
    | Ne modifie rien : renvoie une navigation, une question, ou une
    | action à confirmer (et, pour une commande multiple, la suite).
    */
    public function interpreter(Request $request): JsonResponse
    {
        $donnees = $request->validate([
            'texte'                => ['required', 'string', 'max:500'],
            'historique'           => ['array', 'max:12'],
            'historique.*.role'    => ['required', Rule::in(['user', 'assistant'])],
            'historique.*.content' => ['required', 'string', 'max:600'],
            'page'                 => ['nullable', 'string', 'max:200'],
            ...$this->reglesCommunes(),
        ]);

        $user   = $request->user();
        $langue = $donnees['langue'];

        if (!Catalogue::pour($user) || !$this->interpreteur->estConfigure()) {
            return $this->indisponible($user, $langue);
        }

        $aujourdhui = $donnees['aujourdhui'] ?? null;

        return response()->json($this->interpreteur->interpreter(
            $user,
            $donnees['texte'],
            $langue,
            $donnees['historique'] ?? [],
            $donnees['page'] ?? null,
            $aujourdhui ? Carbon::parse($aujourdhui)->startOfDay() : today(),
            fn (string $action, array $arguments) => $this->executeur->preparer($user, $action, $arguments, $langue, $aujourdhui),
        ));
    }

    /*
    |------------------------------------------------------------------
    | POST /assistant/preparer — Préparer l'action suivante d'une commande multiple
    |------------------------------------------------------------------
    | Lecture seule, comme interpreter(), mais sans passer par l'IA.
    */
    public function preparer(Request $request): JsonResponse
    {
        $donnees = $this->action($request);

        return response()->json(
            $this->executeur->preparer($request->user(), $donnees['action'], $donnees['arguments'] ?? [], $donnees['langue'], $donnees['aujourdhui'] ?? null)
        );
    }

    /*
    |------------------------------------------------------------------
    | POST /assistant/executer — Exécuter une action confirmée
    |------------------------------------------------------------------
    */
    public function executer(Request $request): JsonResponse
    {
        $donnees = $this->action($request);

        return response()->json(
            $this->executeur->executer($request->user(), $donnees['action'], $donnees['arguments'] ?? [], $donnees['langue'], $donnees['aujourdhui'] ?? null)
        );
    }

    private function action(Request $request): array
    {
        return $request->validate([
            'action'    => ['required', 'string', 'max:60'],
            'arguments' => ['array'],
            ...$this->reglesCommunes(),
        ]);
    }

    /** Langue dictée et date du jour vue par le navigateur (le serveur peut être sur un autre fuseau) */
    private function reglesCommunes(): array
    {
        return [
            'langue'     => ['required', Rule::in(['fr', 'en'])],
            'aujourdhui' => ['nullable', 'date_format:Y-m-d'],
        ];
    }

    /** L'assistant ne peut pas répondre : rôle sans assistant, ou clé d'API absente */
    private function indisponible($user, string $langue): JsonResponse
    {
        return Catalogue::pour($user)
            ? $this->message($langue, "L'assistant n'est pas encore configuré : ajoutez ASSISTANT_API_KEY dans le fichier .env.", 'The assistant is not configured yet: add ASSISTANT_API_KEY to the .env file.')
            : $this->message($langue, "L'assistant vocal n'est pas disponible pour votre rôle.", 'The voice assistant is not available for your role.');
    }

    private function message(string $langue, string $fr, string $en): JsonResponse
    {
        return response()->json(['type' => 'message', 'message' => $langue === 'en' ? $en : $fr, 'voix' => $langue]);
    }
}
