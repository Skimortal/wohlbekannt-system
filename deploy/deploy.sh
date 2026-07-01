#!/usr/bin/env bash
#
# Deploy wohlbekannt-system auf den All-Inkl/KAS Shared-Host.
# Fasst AUSSCHLIESSLICH das App-Verzeichnis der Subdomain + die DB an.
#
# Nutzung:
#   1. deploy/.deploy.env aus deploy/.deploy.env.example anlegen und ausfüllen.
#   2. Frontend bauen:  (cd frontend && npm run build)   # erzeugt frontend/dist
#   3. bash deploy/deploy.sh
#
# Läuft von deinem Rechner (nutzt deinen SSH-Key). Nichts wird committet.
set -euo pipefail

HERE="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
ROOT="$(cd "$HERE/.." && pwd)"

# --- Konfiguration/Secrets laden ---
if [ ! -f "$HERE/.deploy.env" ]; then
  echo "FEHLT: $HERE/.deploy.env  (aus .deploy.env.example anlegen)"; exit 1
fi
# shellcheck disable=SC1091
source "$HERE/.deploy.env"

: "${SSH_HOST:?}"; : "${APP_DIR:?}"; : "${DB_USER:?}"; : "${DB_NAME:?}"; : "${DB_HOST:?}"
: "${DB_PASS:?}"; : "${DB_SERVER_VERSION:?}"; : "${ADMIN_EMAIL:?}"; : "${ADMIN_PASS:?}"
: "${ADMIN_NAME:?}"; : "${FRONTEND_URL:?}"; PHP="${PHP:-php84}"

if [ ! -d "$ROOT/frontend/dist" ]; then
  echo "FEHLT: frontend/dist  -> zuerst (cd frontend && npm run build)"; exit 1
fi

# %-Encoding fürs DSN-Passwort (nur die häufigen Sonderzeichen)
enc() { local s="$1"; s="${s//%/%25}"; s="${s//+/%2B}"; s="${s//@/%40}"; s="${s//:/%3A}"; s="${s//\//%2F}"; s="${s//\?/%3F}"; s="${s//#/%23}"; s="${s//&/%26}"; printf '%s' "$s"; }
DB_PASS_ENC="$(enc "$DB_PASS")"

echo "== 1/9  Verzeichnis absichern (deny-all) + Platzhalter sichern =="
ssh "$SSH_HOST" "cd '$APP_DIR' && { [ -f index.htm ] && mv -f index.htm index.htm.allinkl-default || true; } && mkdir -p public"
scp -q "$HERE/root.htaccess" "$SSH_HOST:$APP_DIR/.htaccess"

echo "== 2/9  Backend-Code hochladen (rsync, ohne Secrets/vendor/var) =="
rsync -az \
  --exclude 'var/' --exclude 'vendor/' --exclude '.env.local' \
  --exclude 'config/jwt/' --exclude '.git' --exclude 'tests/' \
  "$ROOT/backend/" "$SSH_HOST:$APP_DIR/"

echo "== 3/9  Frontend (SPA) nach public/ =="
rsync -az "$ROOT/frontend/dist/" "$SSH_HOST:$APP_DIR/public/"
scp -q "$HERE/public.htaccess" "$SSH_HOST:$APP_DIR/public/.htaccess"

echo "== 4/9  .env.local (prod) schreiben =="
APP_SECRET="$(ssh "$SSH_HOST" "$PHP -r 'echo bin2hex(random_bytes(16));'")"
JWT_PASS="$(ssh "$SSH_HOST" "$PHP -r 'echo bin2hex(random_bytes(16));'")"
ssh "$SSH_HOST" "cat > '$APP_DIR/.env.local'" <<ENV
APP_ENV=prod
APP_DEBUG=0
APP_SECRET=${APP_SECRET}
DATABASE_URL="mysql://${DB_USER}:${DB_PASS_ENC}@${DB_HOST}:3306/${DB_NAME}?serverVersion=${DB_SERVER_VERSION}&charset=utf8mb4"
JWT_PASSPHRASE=${JWT_PASS}
MAILER_DSN=null://null
MAILER_FROM=noreply@wohlbekannt.at
APP_FRONTEND_URL=${FRONTEND_URL}
ENV

echo "== 5/9  composer install (--no-dev) =="
ssh "$SSH_HOST" "cd '$APP_DIR' && $PHP /usr/bin/composer install --no-dev --optimize-autoloader --no-interaction"

echo "== 6/9  JWT-Keypair =="
ssh "$SSH_HOST" "cd '$APP_DIR' && $PHP bin/console lexik:jwt:generate-keypair --skip-if-exists"

echo "== 7/9  DB: Migrations + Seed + Admin =="
ssh "$SSH_HOST" "cd '$APP_DIR' && $PHP bin/console doctrine:migrations:migrate -n"
ssh "$SSH_HOST" "cd '$APP_DIR' && $PHP bin/console app:seed"
ssh "$SSH_HOST" "cd '$APP_DIR' && $PHP bin/console app:create-user '$ADMIN_EMAIL' '$ADMIN_PASS' '$ADMIN_NAME' ROLE_ADMIN"

echo "== 8/9  Cache warmup (prod) =="
ssh "$SSH_HOST" "cd '$APP_DIR' && $PHP bin/console cache:clear"

echo "== 9/9  Verifikation (lokaler PHP-Server auf dem Server, nicht öffentlich) =="
ssh "$SSH_HOST" "cd '$APP_DIR' && ($PHP -S 127.0.0.1:8123 public/index.php >/tmp/wb_srv.log 2>&1 & SP=\$!; sleep 2; echo -n 'health: '; curl -s http://127.0.0.1:8123/api/health; echo; TOK=\$(curl -s -X POST http://127.0.0.1:8123/api/login -H 'Content-Type: application/json' -d '{\"email\":\"$ADMIN_EMAIL\",\"password\":\"$ADMIN_PASS\"}'); echo -n 'login: '; echo \"\$TOK\" | head -c 40; echo; kill \$SP)"

echo
echo "FERTIG (App vorbereitet & verifiziert)."
echo "Damit es öffentlich live geht, muss der Kunde im KAS-Panel für die Subdomain erp.wohlbekannt.at:"
echo "  1) das Dokument-Verzeichnis auf  $APP_DIR/public  setzen"
echo "  2) die PHP-Version auf 8.4 stellen"
echo "Danach: https://erp.wohlbekannt.at/api/health und der Login prüfen."
