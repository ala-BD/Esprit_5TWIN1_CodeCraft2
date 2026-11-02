#!/bin/sh
# Démarrage du conteneur « app » : attendre MySQL, migrer, préparer les caches, puis lancer PHP-FPM.
#   retiss-entrypoint --preparer : uniquement la préparation, sans lancer de processus
#   (image tout-en-un Railway : docker/railway/start.sh lance ensuite supervisord).
set -e

if [ "$1" = "php-fpm" ] || [ "$1" = "--preparer" ]; then
    echo "[retiss] Attente de la base de données ${DB_HOST}:${DB_PORT}…"
    essais=0
    until php -r 'new PDO("mysql:host=".getenv("DB_HOST").";port=".getenv("DB_PORT").";dbname=".getenv("DB_DATABASE"), getenv("DB_USERNAME"), getenv("DB_PASSWORD"));' 2>/dev/null; do
        essais=$((essais + 1))
        if [ "$essais" -ge 60 ]; then
            echo "[retiss] Base de données injoignable après 2 minutes." >&2
            exit 1
        fi
        sleep 2
    done

    if [ -z "$APP_KEY" ]; then
        echo "[retiss] APP_KEY est vide : définissez-la dans le fichier .env (php artisan key:generate --show)." >&2
        exit 1
    fi

    php artisan migrate --force --no-interaction

    # Données de démonstration au premier démarrage seulement (base sans utilisateur)
    if [ "${SEED_DEMO:-false}" = "true" ]; then
        utilisateurs=$(php -r '$pdo = new PDO("mysql:host=".getenv("DB_HOST").";port=".getenv("DB_PORT").";dbname=".getenv("DB_DATABASE"), getenv("DB_USERNAME"), getenv("DB_PASSWORD")); echo $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();')
        if [ "$utilisateurs" = "0" ]; then
            echo "[retiss] Base vide : chargement des données de démonstration."
            php artisan db:seed --force --no-interaction
        fi
    fi

    php artisan storage:link --force >/dev/null 2>&1 || true
    php artisan optimize

    chown -R www-data:www-data storage bootstrap/cache
    echo "[retiss] Application prête."
fi

if [ "$1" = "--preparer" ]; then
    exit 0
fi

exec docker-php-entrypoint "$@"
