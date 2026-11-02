# RETISS — DevOps

Conteneurisation, intégration continue, livraison continue et supervision du projet.
Dossier de présentation (diagrammes du processus et du pipeline) : [RETISS-dossier-DevOps.pdf](RETISS-dossier-DevOps.pdf).

## 1. Vue d'ensemble

```
 Développeur ──push──▶ GitHub ──▶ GitHub Actions (CI) ──▶ GHCR (images) ──▶ Serveur (CD)
     │                                  │                                        │
 branche perso ─▶ PR ─▶ integration     ├ qualité (composer, php -l, Pint)       ├ docker compose pull
                                        ├ tests Laravel (PHPUnit, SQLite)        ├ docker compose up -d
                                        ├ tests IA (Python unittest)             └ vérification HTTP
                                        ├ build des 3 images Docker
                                        ├ tests de fumée sur la pile complète
                                        └ scan Trivy
                                                                     Prometheus ◀── /api/metrics, nginx, mysql
                                                                     Grafana    ◀── Prometheus
```

| Fichier | Rôle |
|---|---|
| `Dockerfile` | Image multi-étapes : `vendor` (Composer) → `app` (PHP-FPM 8.4) → `web` (Nginx) |
| `ai-recyclage/Dockerfile` | Micro-service IA FastAPI du module M4, modèle entraîné et testé pendant le build |
| `docker-compose.yml` | Pile complète : app, web, queue, db, ai, mailpit (+ profil `monitoring`) |
| `docker/` | Configuration PHP, PHP-FPM, Nginx et script de démarrage |
| `.github/workflows/ci-cd.yml` | Pipeline CI/CD GitHub Actions |
| `devops/ci/smoke.sh` | Tests de fumée HTTP (utilisés en local et en CI) |
| `devops/monitoring/` | Prometheus (scrape + alertes) et Grafana (source + tableau de bord) |
| `app/Http/Controllers/MetricsController.php` | Endpoint `/api/metrics` au format Prometheus |

## 2. Lancer le projet avec Docker

Prérequis : Docker Desktop (ou Docker Engine + Compose v2).

```bash
# 1. Le fichier .env local doit contenir une APP_KEY (jamais versionnée)
cp .env.example .env
php artisan key:generate          # ou : docker run --rm php:8.4-cli php -r "echo 'base64:'.base64_encode(random_bytes(32));"

# 2. Construire et démarrer
docker compose up -d --build --wait

# 3. Vérifier
bash devops/ci/smoke.sh
```

| Service | URL | Remarque |
|---|---|---|
| Application | http://localhost:8088 | comptes de démo : `admin@retiss.tn` / `password`, etc. |
| Mailpit | http://localhost:8026 | e-mails de vérification et de mot de passe oublié |
| MySQL | `localhost:3307` | utilisateur `retiss` / `retiss` |
| Prometheus | http://localhost:9090 | avec `--profile monitoring` |
| Grafana | http://localhost:3000 | `admin` / `retiss`, tableau de bord « RETISS » |

Au premier démarrage, le conteneur `app` attend MySQL, applique les migrations puis charge les données de
démonstration si la base est vide (`SEED_DEMO=true`, désactivé en production).

```bash
docker compose --profile monitoring up -d   # ajoute Prometheus, Grafana et les exporteurs
docker compose logs -f app                  # journaux Laravel
docker compose exec app php artisan tinker  # console Laravel dans le conteneur
docker compose down                         # arrêt, les données restent
docker compose down -v                      # arrêt + suppression des données
```

Les ports se changent sans toucher au fichier : `RETISS_PORT`, `RETISS_DB_PORT`, `RETISS_MAIL_PORT`,
`RETISS_PROMETHEUS_PORT`, `RETISS_GRAFANA_PORT` dans `.env`.

## 3. Pipeline CI/CD

Déclenché à chaque push sur `main`, `integration`, `ala`, `devops` et à chaque pull request vers
`main`, `integration`, `ala`.

| Étape | Job | Bloquant | Contenu |
|---|---|---|---|
| CI 1 | Qualité du code | oui | `composer validate`, syntaxe `php -l`, Pint sur les fichiers modifiés |
| | | non | Pint sur tout le projet, `composer audit` (avertissements) |
| CI 2 | Tests Laravel | oui | `php artisan test` (PHPUnit, SQLite en mémoire), rapport JUnit |
| CI 2 | Tests IA | oui | entraînement du modèle + `unittest` du micro-service Python |
| CI 3 | Images Docker | oui | build des images `app`, `web`, `ai` avec cache GitHub Actions |
| CI 4 | Tests de fumée | oui | pile complète démarrée, `devops/ci/smoke.sh` (pages, connexion, rôle, IA, métriques) |
| CI 5 | Sécurité | non | scan Trivy de l'image `app` (vulnérabilités HIGH/CRITICAL) |
| CD 1 | Livraison | — | push des images sur GHCR (`main`, `integration`) : tags `<sha>` et `<branche>` |
| CD 2 | Déploiement | oui | `main` et `integration`, si `RAILWAY_ENABLED=true` : `railway up --ci` (ai puis retiss), vérification HTTP |

Le style Pint n'est bloquant que sur les fichiers modifiés : le code existant de l'équipe n'empêche pas la CI de
passer, mais tout nouveau code doit respecter `pint.json`.

### Déploiement sur Railway

Application en ligne : **https://retiss-production.up.railway.app** (projet Railway `retiss`, environnement
`production`). Vercel a été écarté : PHP y tourne en fonctions serverless, sans disque persistant, sans MySQL
ni processus permanent (file de tâches, service IA).

**Architecture**

| Service Railway | Source | Rôle |
|---|---|---|
| `retiss` | dépôt, `docker/railway/Dockerfile` | image tout-en-un : Nginx sur `${PORT}`, PHP-FPM et file de tâches sous `supervisord` ; volume monté sur `/var/www/html/storage/app/public` (photos) ; domaine public |
| `MySQL` | base gérée Railway | données, avec son propre volume |
| `ai` | dossier `ai-recyclage/` | IA M4, réseau privé uniquement : `http://ai.railway.internal:8001` (socket IPv4 + IPv6) |

Railway n'autorise qu'un volume par service, sans partage : Nginx et PHP-FPM, séparés dans docker-compose,
sont donc réunis dans une seule image. Au démarrage, `docker/railway/start.sh` lance l'entrypoint commun
(`retiss-entrypoint --preparer` : attente de MySQL, migrations, seed de démo si `SEED_DEMO=true` et base vide,
`storage:link`, `optimize`), puis `supervisord`, qui relaie SIGTERM à tous les processus.

**Variables du service `retiss`** (valeurs dans Railway, jamais dans le dépôt)

| Variable | Valeur |
|---|---|
| `APP_NAME`, `APP_ENV`, `APP_DEBUG` | `RETISS`, `production`, `false` |
| `APP_URL` | URL publique en `https` |
| `APP_KEY` | générée avec `php artisan key:generate --show --no-ansi` |
| `APP_LOCALE`, `APP_FALLBACK_LOCALE` | `fr` |
| `DB_CONNECTION` | `mysql` |
| `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD` | références `${{MySQL.MYSQLHOST}}`, `${{MySQL.MYSQLPORT}}`, etc. |
| `SESSION_DRIVER`, `CACHE_STORE`, `QUEUE_CONNECTION` | `database` |
| `SESSION_SECURE_COOKIE` | `true` |
| `LOG_CHANNEL`, `LOG_LEVEL` | `stderr`, `debug` (les e-mails `MAIL_MAILER=log` sont écrits au niveau debug) |
| `MAIL_MAILER` | `log` : liens de vérification et de réinitialisation dans les journaux du service |
| `AI_RECYCLAGE_URL` | `http://ai.railway.internal:8001` |
| `METRICS_TOKEN` | valeur aléatoire |
| `SEED_DEMO` | `true` au premier déploiement, puis `false` |
| `RAILWAY_DOCKERFILE_PATH` | `docker/railway/Dockerfile` |
| `FPM_MAX_CHILDREN` | `5` (0,5 Go de mémoire par service sur le forfait gratuit) |
| `GEMINI_API_KEY`, `STRIPE_*`, `AI_API_KEY`, `ASSISTANT_API_KEY` | facultatives : l'application démarre sans elles |

HTTPS : `bootstrap/app.php` fait confiance au proxy de Railway (`trustProxies`), pour que les URL, redirections et
cookies soient en `https`. `railway.json` n'est pas utilisé : Railway l'a déprécié et ne l'accepte plus pour les
nouveaux services ; la configuration passe par les variables et la CLI.

**Activer le déploiement continu**

1. Railway → projet `retiss` → Settings → Tokens : créer un jeton de projet pour l'environnement `production`.
2. GitHub → Settings → Secrets and variables → Actions :

| Type | Nom | Valeur |
|---|---|---|
| Secret | `RAILWAY_TOKEN` | jeton de projet Railway |
| Variable | `RAILWAY_ENABLED` | `true` |
| Variable | `RAILWAY_URL` | `https://retiss-production.up.railway.app` (facultatif) |

Ensuite, chaque push sur `main` ou `integration` dont la CI est verte déploie le service `ai`, puis `retiss`
(`railway up --ci`, CLI figée en 5.71.0), et vérifie `/nginx-health` et `/login`. L'environnement GitHub
`production` permet d'exiger une approbation manuelle avant chaque déploiement.

**Déploiement manuel et retour arrière**

```bash
railway link                                   # projet retiss, environnement production
railway up --service retiss --ci               # déployer le code du dossier courant
railway up ai-recyclage --path-as-root --service ai --ci
METRICS_TOKEN=<jeton> bash devops/ci/smoke.sh https://retiss-production.up.railway.app
```

Retour arrière : Railway → service → onglet *Deployments* → déploiement précédent → *Redeploy*. Le volume et la
base sont conservés ; les migrations déjà appliquées ne sont pas annulées.

**Arrêter après la validation** : la démo est publique avec des mots de passe connus. Supprimer le projet `retiss`
(Railway → Settings → Danger) ou au minimum changer les mots de passe des comptes de démo.

### Lancer les mêmes vérifications en local

```bash
composer validate --no-check-publish
vendor/bin/pint --test app/Http/Controllers/MonControleur.php
php artisan test
cd ai-recyclage && python -m unittest test_moteur -v
docker compose up -d --build --wait && bash devops/ci/smoke.sh
```

## 4. Supervision

`GET /api/metrics` (jeton `Authorization: Bearer $METRICS_TOKEN`, 404 si aucun jeton n'est configuré) expose :

- santé : `retiss_base_disponible`, `retiss_base_latence_secondes`, `retiss_ia_recyclage_disponible`,
  `retiss_gemini_configure`, `retiss_jobs_en_attente`, `retiss_jobs_echoues` ;
- métier, par statut : utilisateurs (par rôle), dons, articles, commandes, projets d'upcycling, devis, lots
  textiles, missions, chiffre des ventes et des devis acceptés, notifications non lues.

Prometheus collecte aussi Nginx (`nginx-prometheus-exporter`) et MySQL (`mysqld-exporter`). Les règles de
`devops/monitoring/alertes.yml` signalent : application injoignable, base indisponible ou lente, IA recyclage
arrêtée, tâches en échec, exporteur Nginx arrêté. Grafana charge automatiquement la source Prometheus et le
tableau de bord `RETISS — Plateforme`.

## 5. Sécurité

- Aucun secret dans le dépôt : `.env` est ignoré, `.env.example` ne contient que des valeurs d'exemple.
- `APP_KEY` est obligatoire : Compose refuse de démarrer sans elle ; la CI en génère une jetable.
- Images : conteneurs IA sans root, OPcache sans relecture des fichiers, `APP_DEBUG=false`, en-têtes de
  sécurité Nginx, `/nginx-status` réservé aux réseaux privés, métriques protégées par jeton.
- Dépendances surveillées par `composer audit` et Trivy à chaque pipeline.

## 6. Dépannage

| Symptôme | Cause probable | Solution |
|---|---|---|
| `APP_KEY manquante` | `.env` absent ou sans clé | `php artisan key:generate` |
| `port is already allocated` | port déjà utilisé | changer `RETISS_PORT` (ou un autre port) dans `.env` |
| `app` reste `unhealthy` | migration ou seed en échec | `docker compose logs app` |
| données de démo absentes | base déjà initialisée | `docker compose down -v` puis `up -d` |
| photos M1 non détourées | ComfyUI non lancé sur l'hôte | lancer ComfyUI sur le port 8188 (optionnel) |
| Railway : erreur 500 « Unsupported cipher » | `APP_KEY` avec des codes couleur | regénérer avec `--no-ansi` |
| Railway : admin en 403 sur `/admin/users` | base remplie avant Spatie | `php artisan migrate` (migration des rôles) |
| Railway : IA indisponible | service `ai` en veille (forfait gratuit) | la première requête le réveille, réessayer |
