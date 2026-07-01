#!/usr/bin/env bash
# Legt einen dedizierten MCP-Benutzer auf dem Live-Server an und schreibt eine
# ausgefüllte Claude-Desktop-Config (mit dem generierten Passwort) nach
# deploy/claude_desktop_config.wohlbekannt.json (gitignored).
set -euo pipefail
HERE="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
# shellcheck disable=SC1091
source "$HERE/.deploy.env"
: "${SSH_HOST:?}"; : "${APP_DIR:?}"; PHP="${PHP:-php84}"

MCP_EMAIL="mcp@wohlbekannt.at"
# openssl liefert endliche Ausgabe -> kein SIGPIPE (tr liest komplett), ~28 alnum Zeichen
MCP_PASS="$(openssl rand -base64 24 | tr -dc 'A-Za-z0-9')"

echo "Lege MCP-Benutzer auf dem Live-Server an ..."
ssh "$SSH_HOST" "cd '$APP_DIR' && $PHP bin/console app:create-user '$MCP_EMAIL' '$MCP_PASS' 'MCP (Claude Desktop)' ROLE_USER"

OUT="$HERE/claude_desktop_config.wohlbekannt.json"
cat > "$OUT" <<JSON
{
  "mcpServers": {
    "wohlbekannt": {
      "command": "node",
      "args": ["/PFAD/zu/wohlbekannt-system/mcp-server/index.mjs"],
      "env": {
        "WB_URL": "https://erp.wohlbekannt.at",
        "WB_EMAIL": "${MCP_EMAIL}",
        "WB_PASSWORD": "${MCP_PASS}"
      }
    }
  }
}
JSON
echo
echo "Fertig. Ausgefüllte Config (enthält das MCP-Passwort, gitignored):"
echo "  $OUT"
echo "Vor dem Ausliefern: den Pfad in \"args\" an den Rechner des Kunden anpassen."
