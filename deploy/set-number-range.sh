#!/usr/bin/env bash
# Setzt den "nächsten Wert" eines Nummernkreises auf dem Live-Server.
# EINMAL-Aktion (z. B. sevDesk-Fortführung), NICHT Teil von deploy.sh.
# Erst nach einem Deploy ausführen (die Command muss hochgeladen sein).
#
#   bash deploy/set-number-range.sh invoice 1037
#
# Weigert sich, den Zähler zurückzusetzen (Schutz vor Doppelnummern) —
# zum Erzwingen als drittes Argument "force" anhängen.
set -euo pipefail
HERE="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
# shellcheck disable=SC1091
source "$HERE/.deploy.env"
: "${SSH_HOST:?}"; : "${APP_DIR:?}"; PHP="${PHP:-php84}"

KEY="${1:?Nummernkreis fehlt (z. B. invoice)}"
NEXT="${2:?Nächster Wert fehlt (z. B. 1037)}"
FORCE=""
[ "${3:-}" = "force" ] && FORCE="--force"

ssh "$SSH_HOST" "cd '$APP_DIR' && $PHP bin/console app:set-number-range '$KEY' '$NEXT' $FORCE"
