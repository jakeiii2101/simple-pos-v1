# SniperPOS Final BIR Operational Acceptance

This checklist closes the SniperPOS BIR-readiness implementation. It is a technical and operational acceptance record, not a BIR accreditation certificate or authority to use the system.

## 1. Taxpayer and RDO validation

- [ ] Registered name, trade name, TIN, branch code, registered address, and RDO code exactly match current BIR registration records.
- [ ] VAT or Non-VAT classification and VAT rate are confirmed with the taxpayer's accountant or RDO.
- [ ] Sales Invoice prefix, starting number, ending number when applicable, and branch assignment match the approved registration.
- [ ] Permit/reference number and date are recorded after issuance by the BIR/RDO.
- [ ] Final invoice wording and required registration details have been reviewed by the taxpayer's accountant or RDO.

## 2. Server preflight

```bash
php artisan migrate --force
php artisan optimize:clear
php artisan database:backup
php artisan bir:preflight --production
php artisan test
```

- [ ] Preflight exits successfully with no blocking failures.
- [ ] Production uses stable HTTPS, `APP_ENV=production`, and `APP_DEBUG=false`.
- [ ] Secure session cookies are enabled and production secrets exist only in server configuration.
- [ ] The web server document root is Laravel's `public/` directory.
- [ ] The scheduler runs every minute and the daily 2:00 AM backup appears in private storage.
- [ ] The newest backup checksum is valid and restoration was tested in a separate database.

## 3. Controlled acceptance transactions

Use clearly identified test products and document every invoice. Never delete or directly edit completed records.

- [ ] Cash, GCash, Card, and Other payments produce correct totals and references.
- [ ] A mixed VATable/VAT-exempt/zero-rated cart produces the expected tax breakdown.
- [ ] Senior Citizen and PWD sales require beneficiary details and calculate correctly.
- [ ] Same-day void and full refund require administrator authorization and appear in the register.
- [ ] Partial refund prevents over-return, records proportional tax reversals, and preserves the original invoice.
- [ ] Dashboard, Reports, BIR exports, and X-reading reconcile the acceptance transactions.
- [ ] Z-reading freezes the correct snapshot and blocks further sales for that date.

## 4. Go-live evidence pack

- [ ] Active BIR Settings and invoice-sequence configuration.
- [ ] Final preflight output and green GitHub Actions run.
- [ ] Sample regular, statutory-discount, void, full-refund, and partial-refund invoices.
- [ ] Sample X/Z readings, BIR Sales CSV, Reversal CSV, and Audit CSV.
- [ ] Backup checksum and test-restore record.
- [ ] Deployment approver, release date/time, commit SHA, operator, and rollback contact.
- [ ] Applicable BIR/RDO registration, permit, and acknowledgment documents.

## 5. Final decision

Go live only when all blocking technical checks pass, manual acceptance is signed, and the taxpayer has obtained the applicable BIR/RDO authority. If an invoice-sequence anomaly appears, stop issuance, preserve records, create a backup, and coordinate with the administrator/accountant before resuming.
