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
| CD 2 | Déploiement | — | `main` seulement, si `DEPLOY_ENABLED=true` : SSH, `compose pull`, `up -d`, vérification |

Le style Pint n'est bloquant que sur les fichiers modifiés : le code existant de l'équipe n'empêche pas la CI de
passer, mais tout nouveau code doit respecter `pint.json`.

### Activer le déploiement

Hébergement prévu : **Railway** (images Docker du projet, MySQL géré, service IA en réseau privé), mis en ligne par
l'intégrateur de l'équipe. Le job `deploiement` ci-dessous (SSH + Docker Compose) reste désactivé d'ici là et sera
adapté à Railway à ce moment-là. Il fonctionne aussi tel quel sur n'importe quel serveur Linux équipé de Docker.

Dans GitHub → Settings → Secrets and variables → Actions :

| Type | Nom | Valeur |
|---|---|---|
| Variable | `DEPLOY_ENABLED` | `true` |
| Variable | `DEPLOY_URL` | `https://retiss.example.tn` |
| Variable | `DEPLOY_PATH` | `/opt/retiss` (par défaut) |
| Secret | `DEPLOY_HOST` | adresse du serveur |
| Secret | `DEPLOY_USER` | utilisateur SSH (membre du groupe `docker`) |
| Secret | `DEPLOY_SSH_KEY` | clé privée SSH |

Sur le serveur : Docker installé et un fichier `/opt/retiss/.env` contenant au minimum `APP_KEY`,
`RETISS_DB_PASSWORD`, `RETISS_DB_ROOT_PASSWORD`, `METRICS_TOKEN`, `GRAFANA_PASSWORD` et les clés d'API.
L'environnement GitHub `production` permet d'exiger une approbation manuelle avant chaque déploiement.

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
