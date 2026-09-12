# SniperPOS Web/PWA V1 Deployment Runbook

## Development / QA

The current GitHub Codespaces preview is:

`https://psychic-succotash-g4r7vr95prvq3vjr6-8080.app.github.dev/`

Laravel links should remain relative so the current HTTPS host is preserved. Do not hardcode localhost URLs in Blade templates.

Typical Codespaces refresh after pulling changes:

```bash
cd /workspaces/simple-pos-v1
git pull origin main
sudo service mysql start
composer install --no-interaction
npm ci
npm run build
php artisan migrate --force
php artisan optimize:clear
php artisan test
php artisan serve --host=0.0.0.0 --port=8080
```

## Production prerequisites

Use a stable HTTPS domain before mobile packaging. Do not ship Android/iOS builds that point to the temporary Codespaces hostname.

Minimum environment values:

```dotenv
APP_NAME="SniperPOS"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-stable-domain.example

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=sniperpos
DB_USERNAME=<production-user>
DB_PASSWORD=<production-secret>

SESSION_DRIVER=database
SESSION_SECURE_COOKIE=true
SESSION_HTTP_ONLY=true
SESSION_SAME_SITE=lax
```

Generate the production `APP_KEY` on the server and never commit it.

## Production deploy sequence

```bash
composer install --no-dev --classmap-authoritative --no-interaction
npm ci
npm run build
php artisan migrate --force
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan database:backup
php artisan bir:preflight --production
```

Ensure the web server serves Laravel from the `public/` directory and forces HTTPS. The application health endpoint is `/up`.

## Post-deploy smoke test

Confirm login, dashboard, POS, one controlled test sale, receipt, inventory, Sales History, Reports, Users, and Audit Log. Verify the manifest and service worker are served over HTTPS and that an installed PWA opens in standalone mode.

Complete `docs/BIR_FINAL_ACCEPTANCE.md`, retain the evidence pack, and obtain the applicable taxpayer/RDO approval before live invoice issuance. A successful technical preflight does not constitute BIR accreditation.

For the controlled sale, use a test product/account and reverse or otherwise reconcile the test transaction according to the site's operational procedure; do not directly edit completed financial records in the database.

## Rollback principle

Application code may be rolled back to the previous known-good release, but do not automatically roll back destructive database migrations after live financial data exists. Restore from a verified backup only when a database rollback is genuinely required and the data impact has been assessed.
