# wohlbekannt-system

ERP/CRM für **RS Wohlbekannt OG** (mobile Bar / Event-Catering, Tirol).

**Phase 1:** Grundsystem mit **Angeboten & Rechnungen** (inkl. optionaler Positionen),
AT-Rechnungslegung, vorbereitet für EU & Schweiz (USt/Reverse-Charge). Später:
Lagerverwaltung u. a.

## Architektur

| Komponente | Stack |
|---|---|
| `backend/` | Symfony 7.4 REST-API, PHP 8.4, Doctrine ORM, MySQL 8, JWT (LexikJWT) |
| `frontend/` | Vue 3 + Vite + TypeScript + Pinia + vue-router + Tailwind 4 (SPA) |
| `mcp-server/` | Node/TS MCP-Server (stdio) für Claude Desktop — auth via Backend-REST |
| PDF | Gotenberg (Chromium HTML→PDF), Twig-Templates |
| `docker-dev/` | Lokale Entwicklung (php-fpm, nginx, mysql, gotenberg, vite) |

> Der Kunde betreibt **nativ** PHP + MySQL. Lokal wird mit Docker gearbeitet; es gibt
> keine Docker-only-Abhängigkeit (Gotenberg läuft beim Kunden als eigener Dienst).

## Lokale Entwicklung

```bash
cd docker-dev
docker compose up -d --build

# Backend-Abhängigkeiten + DB-Schema
docker compose exec php composer install
docker compose exec php php bin/console doctrine:migrations:migrate -n

# JWT-Keypair (einmalig; Passphrase aus backend/.env.local)
docker compose exec php php bin/console lexik:jwt:generate-keypair --skip-if-exists
```

- API / Backend: http://localhost:8088  (`GET /api/health`)
- Frontend (Vite): http://localhost:5173
- MySQL: `localhost:33060` (db `wohlbekannt`, user `app` / `app`)
- Gotenberg: intern `http://gotenberg:3000`

## Verzeichnisse

```
backend/      Symfony API
frontend/     Vue SPA
mcp-server/   MCP-Server (Claude Desktop)
docker-dev/   lokale Docker-Umgebung
docs/         Referenzen (Beispiel-Angebot, Logo, Architektur)
```
