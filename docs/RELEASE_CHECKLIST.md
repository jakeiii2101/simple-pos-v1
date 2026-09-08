# SniperPOS Web/PWA V1 Release Checklist

This checklist is the final gate before tagging the Web/PWA release candidate and beginning Android/iOS packaging.

## Automated gates

- [ ] `composer validate --no-check-publish` passes.
- [ ] `composer install --no-interaction --prefer-dist --no-progress` succeeds.
- [ ] `npm ci` succeeds.
- [ ] `npm run build` succeeds and creates `public/build/manifest.json`.
- [ ] Clean migrations succeed on an empty database.
- [ ] `php artisan test` is fully green.
- [ ] GitHub Actions `SniperPOS CI` is green on the release-candidate PR.

## Functional regression

- [ ] Admin login/logout works.
- [ ] Cashier login/logout works.
- [ ] Inactive users cannot authenticate or use protected pages.
- [ ] Admin-only modules reject cashier access.
- [ ] Categories CRUD works with relationship protections.
- [ ] Products CRUD works; SKU/barcode validation works; price changes are audited.
- [ ] Inventory stock-in/out/adjustment works and cannot make stock negative.
- [ ] POS product search by name/SKU/barcode works.
- [ ] Cart quantity, remove, clear, fixed discount, and percentage discount work.
- [ ] Cash checkout calculates tender/change correctly.
- [ ] GCash, Card, and Other require a reference and complete correctly.
- [ ] Checkout revalidates current product price/status/stock server-side.
- [ ] Receipt displays SniperPOS branding, discounts, payment details, and New Sale.
- [ ] Completed sale financial history cannot be edited/deleted.
- [ ] Sales History presets and custom dates work.
- [ ] Dashboard KPIs reflect current database values.
- [ ] Reports show gross sales, discounts, net sales, payment breakdown, top products, and inventory metrics.
- [ ] Audit Log is admin-only and records critical events.

## PWA and responsive QA

- [ ] Manifest loads and identifies the app as SniperPOS.
- [ ] 192x192 and 512x512 icons load correctly.
- [ ] Service worker registers without console errors.
- [ ] Authenticated HTML pages are not available from cache after going offline.
- [ ] Offline navigation displays the branded offline fallback.
- [ ] Android Chrome installation works.
- [ ] Installed Android PWA launches in standalone mode.
- [ ] iPhone Safari Add to Home Screen behavior is checked when an iPhone/iPad is available.
- [ ] Dashboard, POS, receipt, sales, inventory, reports, users, and audit pages have no blocking horizontal overflow on phone/tablet widths.
- [ ] POS remains usable with touch controls and on-screen keyboard visible.

## Production configuration gate

Before public deployment, the production environment must use a stable HTTPS domain. The temporary GitHub Codespaces URL is for development/QA only.

- [ ] `APP_ENV=production`
- [ ] `APP_DEBUG=false`
- [ ] `APP_URL=https://<stable-production-domain>`
- [ ] Strong production `APP_KEY` generated and stored only on the server.
- [ ] MySQL production credentials stored only in environment configuration.
- [ ] `SESSION_SECURE_COOKIE=true`
- [ ] `SESSION_HTTP_ONLY=true`
- [ ] `SESSION_SAME_SITE=lax`
- [ ] HTTPS certificate is valid and redirects HTTP to HTTPS.
- [ ] Scheduler/queue configuration reviewed if enabled.
- [ ] Backup and restore procedure tested.
- [ ] No passwords, DB credentials, APP_KEY, payment secrets, or other production secrets are committed to Git.

## Release decision

Only tag the Web/PWA V1 release candidate when automated CI is green and the manual browser/device checks above are confirmed. Android and iOS wrappers must point to a stable production HTTPS deployment, not the GitHub Codespaces preview URL.
