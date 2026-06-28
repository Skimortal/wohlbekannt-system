# wohlbekannt MCP-Server

Verbindet **Claude Desktop** mit dem wohlbekannt-Backend. Der Server meldet sich
als Backend-User an (JWT) und stellt **lesende** Tools bereit, die dieselben
Rechte wie die Web-App respektieren. (Schreib-Tools folgen später, gemeinsam mit
dem Kunden.)

## Einrichtung in Claude Desktop

`claude_desktop_config.json`:

```json
{
  "mcpServers": {
    "wohlbekannt": {
      "command": "node",
      "args": ["/pfad/zu/wohlbekannt-system/mcp-server/index.mjs"],
      "env": {
        "WB_URL": "https://erp.example.at",
        "WB_EMAIL": "user@example.at",
        "WB_PASSWORD": "••••••"
      }
    }
  }
}
```

Vorher `npm install` im Ordner `mcp-server/` ausführen.

> Der **Claude-API-Key** wird vom Kunden in Claude Desktop hinterlegt und von ihm
> selbst bezahlt — er wird hier nirgends verarbeitet.

## Tools (lesend)

| Tool | Beschreibung |
|---|---|
| `wb_health` | Backend erreichbar? |
| `wb_customers` | Kunden auflisten/suchen |
| `wb_articles` | Leistungskatalog auflisten/suchen |
| `wb_quotes` | Angebote auflisten (Filter: `status`) |
| `wb_quote` | Einzelnes Angebot inkl. Positionen |
| `wb_invoices` | Rechnungen auflisten (Filter: `status`, `type`) |
| `wb_invoice` | Einzelne Rechnung inkl. Positionen & Zahlungen |
| `wb_revenue` | Umsatz-Übersicht (bezahlt/offen/überfällig) |
