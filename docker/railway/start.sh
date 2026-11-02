#!/bin/sh
# Démarrage de l'image Railway :
#   1. préparation commune (attente MySQL, migrations, seed de démo, storage:link, optimize)
#   2. configuration Nginx sur le port fourni par Railway (${PORT}, 8080 par défaut)
#   3. supervisord au premier plan : PHP-FPM, file de tâches et Nginx.
#      SIGTERM (arrêt ou redéploiement) est relayé par supervisord à tous les processus.
set -e

PORT="${PORT:-8080}"

retiss-entrypoint --preparer

# Volume Railway monté en root sur storage/app/public : PHP-FPM écrit en www-data
chown -R www-data:www-data /var/www/html/storage/app/public

# Même configuration que docker-compose, avec PHP-FPM local et le port Railway (IPv4 + IPv6)
sed -e "s/listen 80 default_server;/listen ${PORT} default_server;\n    listen [::]:${PORT} default_server;/" \
    -e "s/fastcgi_pass app:9000;/fastcgi_pass 127.0.0.1:9000;/" \
    /etc/retiss/nginx-default.conf > /etc/nginx/http.d/retiss.conf

nginx -t -q

# Forfait gratuit Railway : 0,5 Go de mémoire par service, d'où moins de processus PHP-FPM
# que dans docker-compose (20). Réglable avec la variable FPM_MAX_CHILDREN.
cat > /usr/local/etc/php-fpm.d/zzz-railway.conf <<EOF
[www]
pm.max_children = ${FPM_MAX_CHILDREN:-5}
pm.start_servers = 2
pm.min_spare_servers = 1
pm.max_spare_servers = 3
EOF

echo "[retiss] Démarrage de PHP-FPM, de la file de tâches et de Nginx sur le port ${PORT}."
exec supervisord -n -c /etc/supervisord.conf
