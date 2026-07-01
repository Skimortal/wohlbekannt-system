# wohlbekannt-system — Projektkontext

ERP/CRM für **RS Wohlbekannt OG** (mobile Bar / Event-Catering, Kematen in Tirol).
Auftrag von Mindstream (Aleksandar Stojakovic). Bisher nutzte der Kunde **sevDesk** —
daran orientieren wir uns funktional.

## Phasen
- **Phase 1 (aktuell):** Grundsystem — Kunden, Leistungskatalog, **Angebote & Rechnungen**
  inkl. **optionaler Positionen**, AT-Rechnungslegung, EU/CH-vorbereitet (USt). Plus
  **MCP-Server** für Claude Desktop.
- Später: Lagerverwaltung, weitere Funktionen.

## Architektur-Entscheidungen
- Stack gespiegelt von `../mc-cockpit`: Symfony 7.4 REST-API + LexikJWT, Vue 3 + Vite +
  Pinia + Tailwind 4 (SPA), Node/TS MCP-Server (stdio).
- **DB: MySQL 8** (Kunde läuft nativ MySQL) — nicht PostgreSQL wie mc-cockpit.
- **PDF: mpdf** (pure PHP) + Twig-Templates (Tabellen-Layout, DejaVu-Sans). Ursprünglich
  Gotenberg geplant, aber der Kunde läuft auf **All-Inkl/KAS Shared-Hosting** (kein Docker/
  Dauer-Dienste möglich) → auf mpdf umgestellt, damit alles auf dem Hoster läuft.
- Lokal **Docker** (`docker-dev/`); Kunde deployt **nativ** auf Shared-Hosting. Keine
  Docker-/Service-only-Abhängigkeiten einbauen.

## Fachliche Eckpunkte
- **Kein RKSV / keine Registrierkasse** — nur normale Rechnungslegung für Dienstleistungen
  außerhalb des Café-Bargeschäfts.
- **Ein Mandant** (RS Wohlbekannt OG), aber Aussteller-Daten in `CompanySettings`-Entity
  (nicht hartcodiert) → Multi-Mandant später leicht nachrüstbar.
- **Optionale Positionen** sind das Kern-Feature: im Angebot als „Opt." markiert, NICHT in
  der Hauptsumme, separat als „Summe optionaler Positionen brutto" ausgewiesen
  (siehe `docs/beispiel-angebot-AN-1032.pdf`).
- **Lückenlose, getrennte Nummernkreise** (Angebot `AN-`, Rechnung `RE-`, Kunde, Storno) —
  atomar hochzählen.
- **Storno & Gutschrift** berücksichtigen (Original nie löschen, Storno verlinkt + negativ).
- **USt:** AT 20/13/10 %, EU-B2B mit gültiger USt-ID → Reverse-Charge (Hinweistext),
  CH → Drittland/Export. Summary nach Steuersatz gruppiert. Regelbesteuert (kein KU).
- **E-Rechnung (Peppol/ebInterface):** noch offen — Kunde wird gefragt.

## Brand
- Sand/Champagne **#E6C7A9**, nahezu Schwarz **#0E1214**. Minimal, viel Weißraum, elegant.
- Logo: `docs/assets/wohlbekannt-logo.svg`. Tailwind-Tokens in `frontend/src/style.css`.

## MCP
- stdio, authentifiziert sich als Backend-User (JWT) via REST.
- Erst **lesend** (Angebote/Rechnungen/Umsätze/Kunden), Schreiben später mit dem Kunden.
- Kunde legt **eigenen Claude-API-Key** in Claude Desktop an und zahlt ihn selbst.

## Arbeitsweise
- Push nach `main` bei DIESEM Projekt durch den Agent erlaubt (kein Prod-Deploy).
- composer/php/symfony nur via Docker (lokal kein natives PHP installiert).

## Repo
- GitHub: https://github.com/Skimortal/wohlbekannt-system.git
- Referenzen: `../mc-cockpit` (Stack-Vorlage), inEasy.at (älteres Vorgängerprojekt, derzeit
  nicht erreichbar).
