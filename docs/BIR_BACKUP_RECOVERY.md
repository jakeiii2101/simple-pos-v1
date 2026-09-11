# SniperPOS Backup and Recovery Runbook

## Automated backups

SniperPOS schedules `php artisan database:backup` every day at 2:00 AM. The server must run Laravel's scheduler every minute:

```cron
* * * * * cd /path/to/sniperpos && php artisan schedule:run >> /dev/null 2>&1
```

Backups are stored privately under `storage/app/private/backups`, accompanied by a SHA-256 checksum, and retained for 30 days. Copy backups to a separate encrypted server or object-storage account; a backup on the same server is not sufficient disaster recovery.

## Verification

Run a manual backup after deployment:

```bash
php artisan database:backup
php artisan schedule:list
```

Download the newest backup from **System Readiness** and compare it with its `.sha256` file on the server:

```bash
sha256sum -c storage/app/private/backups/sniperpos-YYYYMMDD-HHMMSS.sql.sha256
```

Perform a test restoration into a separate non-production database at least monthly.

## Controlled MySQL restoration

Restoration is intentionally unavailable in the web interface. It must be performed by an authorized server administrator during a maintenance window.

1. Confirm the target server, database name, and selected backup.
2. Place SniperPOS in maintenance mode: `php artisan down`.
3. Create a fresh pre-restore backup: `php artisan database:backup`.
4. Verify the selected backup's SHA-256 checksum.
5. Restore into a separate temporary database first and validate invoice counts, latest invoice number, users, sales totals, and migrations.
6. Only after validation, restore into the intended production database using the hosting provider's database tool or MySQL client.
7. Run `php artisan migrate --force` and `php artisan optimize:clear`.
8. Open **System Readiness** and investigate every reported sequence gap before resuming sales.
9. Return the application to service with `php artisan up`.

Never overwrite production without a verified current backup and a documented approval.
