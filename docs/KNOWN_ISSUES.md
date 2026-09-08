# SniperPOS Web/PWA V1 — Known Issues & Release Boundaries

## Intentional V1 boundaries

- SniperPOS is online-first. Offline sales and offline-to-online transaction synchronization are deferred to a later phase.
- When disconnected, the PWA displays a static offline page. Authenticated sales, reports, receipts, and other business HTML pages are deliberately not cached.
- V1 permissions use the Admin and Cashier roles. Complex custom permissions are deferred.
- Customer loyalty, coupon campaigns, advanced multi-branch functions, and other non-roadmap enhancements are outside this release.

## Mobile packaging dependency

The GitHub Codespaces preview URL is suitable for development and QA but is temporary. Android and iOS production packages require a stable production HTTPS URL first. Mobile packages must not contain database credentials, `APP_KEY`, admin passwords, or other server secrets.

## Manual QA still required before RC tag

Automated tests cannot prove physical-device behavior. Before tagging the release candidate, verify at minimum:

- Android Chrome install/launch/standalone behavior.
- Login and session behavior over the HTTPS preview or production-like host.
- POS touch workflow and on-screen keyboard behavior.
- Receipt printing/share behavior available on the test device/browser.
- Phone/tablet responsive layouts with no blocking overflow.
- Offline fallback after the service worker has installed.
- iPhone/iPad Safari/Add to Home Screen when Apple hardware is available.

## Production environment

The repository `.env.example` points to the current Codespaces HTTPS host for development convenience. A production deployment must replace it with the stable production domain and set `APP_ENV=production` and `APP_DEBUG=false`.
