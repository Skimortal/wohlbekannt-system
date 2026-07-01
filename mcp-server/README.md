# wohlbekannt MCP-Server

Verbindet **Claude Desktop** mit dem wohlbekannt-Backend. Der Server meldet sich
als Backend-User an (JWT) und stellt lesende **und schreibende** Tools bereit, die
dieselben Rechte wie die Web-App respektieren.

## Einrichtung in Claude Desktop

Vorlage: `claude_desktop_config.example.json` (Pfad, URL und Zugangsdaten anpassen).

```json
{
  "mcpServers": {
    "wohlbekannt": {
      "command": "node",
      "args": ["/PFAD/zu/wohlbekannt-system/mcp-server/index.mjs"],
      "env": {
        "WB_URL": "https://erp.wohlbekannt.at",
        "WB_EMAIL": "mcp@wohlbekannt.at",
        "WB_PASSWORD": "••••••"
      }
    }
  }
}
```

Vorher `npm install` im Ordner `mcp-server/` ausführen. Empfohlen ist ein
**dedizierter Benutzer** (z. B. `mcp@wohlbekannt.at`) statt eines persönlichen
Kontos — anlegen via `deploy/setup-mcp-user.sh`.

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

## Tools (schreibend)

| Tool | Beschreibung |
|---|---|
| `wb_create_customer` | Kunden anlegen (Name, E-Mail, Adresse, USt-ID …) |
| `wb_create_quote` | Angebot als Entwurf anlegen (Preise in Euro, optionale Positionen möglich) |
| `wb_send_quote` | Angebot versenden (vergibt Angebotsnummer AN-…) |
| `wb_invoice_from_quote` | Rechnungs-Entwurf aus angenommenem Angebot (optionale Positionen wählbar) |
| `wb_finalize_invoice` | Rechnung festschreiben (Nummer RE-… + Fälligkeit) |
| `wb_record_payment` | Zahlung erfassen (Betrag in Euro) |

Beträge werden bei den Schreib-Tools in **Euro** angegeben; die Umrechnung in Cent
übernimmt der Server. Nummern werden erst beim Versenden/Festschreiben vergeben
(lückenlose Nummernkreise).
