# Deployment (All-Inkl / KAS Shared-Hosting)

Zielumgebung: **All-Inkl/KAS**, Subdomain `erp.wohlbekannt.at`, PHP 8.4 (`php84`),
**MariaDB 10.6** (Host `localhost`). PDF via mpdf (kein Gotenberg nötig).

## Ablauf

1. **Secrets-Datei anlegen** (einmalig, gitignored):
   ```bash
   cp deploy/.deploy.env.example deploy/.deploy.env
   # DB_PASS und ADMIN_PASS eintragen
   ```
2. **Frontend bauen**:
   ```bash
   (cd frontend && npm ci && npm run build)   # oder via Docker
   ```
3. **Deploy** (läuft von deinem Rechner mit deinem SSH-Key):
   ```bash
   bash deploy/deploy.sh
   ```

Das Skript fasst **nur** `APP_DIR` (die Subdomain) und die DB `d0477b90` an:
Code hochladen (rsync), `composer install --no-dev`, `.env.local` schreiben,
JWT-Keys, Migrations, Seed (Standard-Nummernkreise), Admin-User, Cache-Warmup,
Verifikation über einen lokalen PHP-Server auf dem Server (nicht öffentlich).

Während des Deploys ist das Verzeichnis per `Require all denied`-`.htaccess`
gesperrt.

## Livegang (macht der Kunde im KAS-Panel)

- **Dokument-Verzeichnis** der Subdomain `erp.wohlbekannt.at` auf
  `.../erp.wohlbekannt.at/public` setzen.
- **PHP-Version** der Subdomain auf **8.4**.

Danach prüfen: `https://erp.wohlbekannt.at/api/health` und Login.

## Offen / später

- **SMTP** für Passwort-Reset-Mails: in `.env.local` `MAILER_DSN` + `MAILER_FROM`
  auf den echten Kunden-SMTP setzen (aktuell `null://null`).
- Erneutes Deploy = Skript nochmal laufen lassen (rsync aktualisiert; Migrations
  laufen idempotent, Seed/Admin sind idempotent).
