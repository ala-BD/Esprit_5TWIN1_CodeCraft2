<?php

namespace App\Console\Commands;

use App\Models\Article;
use App\Models\Favori;
use App\Models\RechercheSauvegardee;
use App\Notifications\FavoriBientotEnRuptureNotification;
use App\Notifications\FavoriPrixBaisseNotification;
use App\Notifications\NouvelArticleRechercheNotification;
use Illuminate\Console\Command;

class EnvoyerAlertesQuotidiennes extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'retiss:alertes-quotidiennes';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Envoie les alertes intelligentes quotidiennes (baisses de prix, articles bientôt épuisés, nouvelles correspondances de recherche)';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('🌿 [RETISS] Démarrage du traitement des alertes intelligentes quotidiennes...');
        $nbAlertesPrix = 0;
        $nbAlertesStock = 0;
        $nbAlertesRecherche = 0;

        // 1. Alertes sur articles favoris presque épuisés (stock = 1 ou 2)
        $favorisLowStock = Favori::with(['user', 'article'])
            ->whereHas('article', function ($q) {
                $q->where('statut', Article::STATUT_DISPONIBLE)
                  ->where('stock', '>', 0)
                  ->where('stock', '<=', 2);
            })
            ->get();

        foreach ($favorisLowStock as $favori) {
            if ($favori->article && $favori->user && $favori->user_id !== $favori->article->user_id) {
                $favori->user->notify(new FavoriBientotEnRuptureNotification($favori->article, (int) $favori->article->stock));
                $nbAlertesStock++;
            }
        }

        // 2. Alertes sur les baisses de prix constatées
        $favorisPrixReduit = Favori::with(['user', 'article'])
            ->whereHas('article', function ($q) {
                $q->where('statut', Article::STATUT_DISPONIBLE);
            })
            ->get()
            ->filter(fn($f) => $f->prix_au_moment_ajout && $f->article->prix < $f->prix_au_moment_ajout);

        foreach ($favorisPrixReduit as $favori) {
            if ($favori->article && $favori->user && $favori->user_id !== $favori->article->user_id) {
                $ancien = (float) $favori->prix_au_moment_ajout;
                $nouveau = (float) $favori->article->prix;
                $favori->user->notify(new FavoriPrixBaisseNotification($favori->article, $ancien, $nouveau));
                $favori->update(['prix_au_moment_ajout' => $nouveau]);
                $nbAlertesPrix++;
            }
        }

        // 3. Alertes sur les nouveaux articles des dernières 24h correspondant aux recherches sauvegardées
        $nouveauxArticles24h = Article::where('statut', Article::STATUT_DISPONIBLE)
            ->where('created_at', '>=', now()->subDay())
            ->get();

        if ($nouveauxArticles24h->isNotEmpty()) {
            $recherches = RechercheSauvegardee::with('user')->get();
            foreach ($recherches as $recherche) {
                foreach ($nouveauxArticles24h as $art) {
                    if ($art->user_id === $recherche->user_id) {
                        continue;
                    }
                    $matches = $recherche->buildArticleQuery()
                        ->where('id', $art->id)
                        ->exists();

                    if ($matches) {
                        $recherche->user->notify(new NouvelArticleRechercheNotification($art, $recherche));
                        $recherche->update(['derniere_notification_at' => now()]);
                        $nbAlertesRecherche++;
                    }
                }
            }
        }

        $this->info("✅ Bilan des alertes envoyées :");
        $this->line("   - {$nbAlertesStock} alerte(s) de stock presque épuisé");
        $this->line("   - {$nbAlertesPrix} alerte(s) de baisse de prix");
        $this->line("   - {$nbAlertesRecherche} notification(s) de nouveaux articles pour recherches sauvegardées");

        return self::SUCCESS;
    }
}
