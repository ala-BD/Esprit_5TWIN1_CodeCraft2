#!/usr/bin/env bash
# Tests de fumée sur la pile docker-compose démarrée (local ou CI).
#   bash devops/ci/smoke.sh [http://localhost:8088]
# Vérifie : pages publiques, connexion d'un compte de démo, redirection par rôle,
# micro-service IA, métriques Prometheus. Sort en erreur au premier échec.
set -euo pipefail

BASE="${1:-${RETISS_URL:-http://localhost:8088}}"
TOKEN="${METRICS_TOKEN:-retiss-metrics}"
COOKIES="$(mktemp)"
trap 'rm -f "$COOKIES"' EXIT
echecs=0

ok()    { echo "  [OK]    $1"; }
echec() { echo "  [ECHEC] $1"; echecs=$((echecs + 1)); }

statut() { curl -s -o /dev/null -w '%{http_code}' "$@"; }

echo "== Tests de fumée sur $BASE"

# 1. Pages publiques
for chemin in / /login /register /forgot-password /nginx-health; do
    code=$(statut "$BASE$chemin")
    [ "$code" = "200" ] && ok "GET $chemin -> 200" || echec "GET $chemin -> $code"
done

# 2. Une page protégée renvoie vers la connexion
code=$(statut "$BASE/dashboard")
[ "$code" = "302" ] && ok "GET /dashboard sans session -> 302" || echec "GET /dashboard sans session -> $code"

# 3. Connexion de l'administrateur de démo (jeton CSRF + cookie de session)
jeton=$(curl -s -c "$COOKIES" "$BASE/login" | sed -n 's/.*name="_token" value="\([^"]*\)".*/\1/p' | head -1)
if [ -z "$jeton" ]; then
    echec "jeton CSRF introuvable sur /login"
else
    cible=$(curl -s -b "$COOKIES" -c "$COOKIES" -o /dev/null -w '%{redirect_url}' \
        --data-urlencode "_token=$jeton" \
        --data-urlencode "email=admin@retiss.tn" \
        --data-urlencode "password=password" \
        "$BASE/login")
    case "$cible" in
        */admin/users) ok "connexion admin -> $cible" ;;
        *)             echec "connexion admin -> '${cible:-pas de redirection}'" ;;
    esac
    code=$(statut -b "$COOKIES" "$BASE/admin/users")
    [ "$code" = "200" ] && ok "GET /admin/users connecté -> 200" || echec "GET /admin/users connecté -> $code"
fi

# 4. Métriques : jeton exigé, base et IA joignables depuis Laravel
code=$(statut "$BASE/api/metrics")
[ "$code" = "401" ] && ok "GET /api/metrics sans jeton -> 401" || echec "GET /api/metrics sans jeton -> $code"

metriques=$(curl -s -H "Authorization: Bearer $TOKEN" "$BASE/api/metrics" || true)
for attendu in 'retiss_base_disponible 1' 'retiss_ia_recyclage_disponible 1'; do
    grep -q "^$attendu" <<<"$metriques" && ok "métrique '$attendu'" || echec "métrique '$attendu' absente"
done

echo
if [ "$echecs" -gt 0 ]; then
    echo "== $echecs test(s) en échec"
    exit 1
fi
echo "== Tous les tests de fumée sont passés"
