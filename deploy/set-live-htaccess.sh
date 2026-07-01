#!/usr/bin/env bash
# Schaltet das Root-.htaccess von deny-all auf Live-Routing (nach public/) und
# testet die öffentliche URL. Nutzen, wenn der Subdomain-Docroot (noch) nicht
# auf /public zeigt.
set -euo pipefail
HERE="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
# shellcheck disable=SC1091
source "$HERE/.deploy.env"
: "${SSH_HOST:?}"; : "${APP_DIR:?}"

scp -q "$HERE/root-live.htaccess" "$SSH_HOST:$APP_DIR/.htaccess"
echo "Root-.htaccess auf Live-Routing gesetzt."
sleep 1

BASE="https://erp.wohlbekannt.at"
echo -n "health : "; curl -s -m 20 -w "  [HTTP %{http_code}]\n" "$BASE/api/health"
echo -n "root   : "; curl -s -m 20 -o /dev/null -w "HTTP %{http_code}\n" "$BASE/"
echo -n "login  : "; curl -s -m 20 -X POST "$BASE/api/login" -H 'Content-Type: application/json' -d "{\"email\":\"$ADMIN_EMAIL\",\"password\":\"$ADMIN_PASS\"}" | head -c 40; echo
echo -n ".env   : "; curl -s -m 20 -o /dev/null -w "HTTP %{http_code} (403/404 = gut)\n" "$BASE/.env.local"
echo -n "src    : "; curl -s -m 20 -o /dev/null -w "HTTP %{http_code} (403/404 = gut)\n" "$BASE/src/Kernel.php"
