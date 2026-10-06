<?php

namespace App\Services\Assistant;

use App\Models\Tournee;
use App\Models\User;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Transcrit un enregistrement vocal avec un modèle Whisper.
 *
 * Bien plus fiable que la dictée du navigateur pour les noms propres : on fournit au
 * modèle le vocabulaire de l'espace (noms des utilisateurs, zones des tournées).
 */
class Transcripteur
{
    /** @return array{texte: string}|array{erreur: 'reseau'|'cle'|'quota'|'service'} */
    public function transcrire(UploadedFile $audio, string $langue, User $user): array
    {
        $vocabulaire = $this->vocabulaire($user, $langue);

        try {
            $reponse = Http::withToken(config('assistant.api_key'))
                ->timeout(config('assistant.timeout'))
                ->acceptJson()
                ->attach('file', $audio->getContent(), 'commande.' . ($audio->guessExtension() ?: 'webm'))
                ->post(config('assistant.base_url') . '/audio/transcriptions', [
                    'model'           => config('assistant.transcription_model'),
                    'language'        => $langue === 'en' ? 'en' : 'fr',
                    'prompt'          => $vocabulaire,
                    'response_format' => 'verbose_json',
                    'temperature'     => 0,
                ]);
        } catch (ConnectionException $e) {
            return ['erreur' => 'reseau'];
        }

        if ($reponse->failed()) {
            Log::warning('Assistant vocal : erreur de transcription', ['statut' => $reponse->status(), 'corps' => $reponse->body()]);

            return ['erreur' => match ($reponse->status()) {
                401, 403 => 'cle',
                429      => 'quota',
                default  => 'service',
            }];
        }

        $texte = trim((string) $reponse->json('text'));

        $inaudible = $this->estInaudible($reponse->json('segments') ?? []) || $this->estUneRecitation($texte, $vocabulaire);

        return ['texte' => $inaudible ? '' : $texte];
    }

    /**
     * Whisper indique, pour chaque segment, s'il pense qu'il n'y avait pas de parole et
     * à quel point il est sûr de lui. Un enregistrement entièrement douteux est écarté
     * plutôt que de transformer du bruit en commande.
     */
    private function estInaudible(array $segments): bool
    {
        if (!$segments) {
            return false;
        }

        $douteux = array_filter($segments, fn (array $segment) => ($segment['no_speech_prob'] ?? 0) > 0.6 || ($segment['avg_logprob'] ?? 0) < -1.0);

        return count($douteux) === count($segments);
    }

    /**
     * Sur un son inaudible, Whisper « invente » en récitant le vocabulaire qu'on lui a fourni
     * (une suite de noms sans verbe). Une vraie commande contient des mots qui n'y figurent pas.
     */
    private function estUneRecitation(string $texte, string $vocabulaire): bool
    {
        $mots = fn (string $t) => array_values(array_filter(preg_split('/[^\p{L}\p{N}]+/u', Str::lower(Str::ascii($t)))));

        $dits = $mots($texte);

        if (count($dits) < 3) {
            return false;
        }

        $connus   = array_flip($mots($vocabulaire));
        $recopies = count(array_filter($dits, fn (string $mot) => isset($connus[$mot])));

        return $recopies / count($dits) >= 0.8;
    }

    /** Mots que la personne risque de prononcer : oriente l'orthographe des noms propres */
    private function vocabulaire(User $user, string $langue): string
    {
        if ($user->isAdmin()) {
            return User::latest('id')->limit(30)->get()->map->full_name->join(', ') . '.';
        }

        return Tournee::where('user_id', $user->id)->latest('date')->limit(15)->pluck('zone')->unique()->join(', ') . '.';
    }
}
