<?php

/*
|--------------------------------------------------------------------------
| Assistant vocal
|--------------------------------------------------------------------------
|
| L'assistant envoie la commande dictée à un modèle de langage qui choisit
| l'action à effectuer. Toute API compatible « OpenAI chat completions »
| avec appel de fonctions convient : Groq (gratuit, par défaut), Ollama en
| local (http://localhost:11434/v1), etc.
|
*/

return [

    'base_url' => rtrim(env('ASSISTANT_BASE_URL', 'https://api.groq.com/openai/v1'), '/'),

    'api_key' => env('ASSISTANT_API_KEY'),

    'model' => env('ASSISTANT_MODEL', 'openai/gpt-oss-120b'),

    'timeout' => (int) env('ASSISTANT_TIMEOUT', 20),

    // Options ajoutées telles quelles à la requête. « reasoning_effort » réduit le temps de
    // réponse et la consommation de jetons des modèles gpt-oss ; à vider pour un autre modèle.
    'options' => json_decode(env('ASSISTANT_OPTIONS', '{"reasoning_effort":"low"}'), true) ?: [],

    // Transcription de la voix (API « audio/transcriptions », modèle Whisper)
    'transcription_model' => env('ASSISTANT_TRANSCRIPTION_MODEL', 'whisper-large-v3-turbo'),

    // Voix naturelle pour les réponses en anglais (API « audio/speech », modèle Orpheus).
    // Laisser ASSISTANT_VOICE_MODEL vide pour n'utiliser que la voix du navigateur.
    'voice_model' => env('ASSISTANT_VOICE_MODEL', 'canopylabs/orpheus-v1-english'),

    'voice' => env('ASSISTANT_VOICE', 'hannah'),

];
