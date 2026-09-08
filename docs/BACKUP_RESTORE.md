# SniperPOS Backup & Restore

SniperPOS stores financial, payment, inventory, user, and audit history in MySQL. Backups must be treated as sensitive business data.

## Backup

Run from a secure server shell using credentials supplied through the environment or an interactive password prompt. Do not place database passwords in shell history, scripts committed to Git, or documentation.

```bash
mkdir -p backups
mysqldump \
  --single-transaction \
  --routines \
  --triggers \
  --set-gtid-purged=OFF \
  -h 127.0.0.1 \
  -u <db-user> \
  -p \
  <db-name> > backups/sniperpos-$(date +%Y%m%d-%H%M%S).sql
```

After creation, restrict file access and copy the backup to an approved encrypted/off-server location. Keep more than one recovery point according to the business retention policy.

## Verify a backup

A backup is not considered valid until it can be restored into a non-production database.

```bash
mysql -h 127.0.0.1 -u <db-user> -p -e "CREATE DATABASE sniperpos_restore_test CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
mysql -h 127.0.0.1 -u <db-user> -p sniperpos_restore_test < backups/<backup-file>.sql
```

Point a temporary non-production SniperPOS environment at the restored database and verify users, products, sales, sale items, payments, stock movements, and audit logs.

## Production restore

1. Put the application into maintenance mode: `php artisan down`.
2. Take a final backup of the current production database before replacing anything.
3. Restore only a verified backup into the intended production database.
4. Run `php artisan migrate --force` only if the restored database is behind the deployed application version.
5. Run `php artisan optimize:clear` and rebuild caches as required.
6. Verify `/up`, authentication, dashboard totals, a sample historical sale/receipt, inventory totals, and Audit Log.
7. Bring the application back: `php artisan up`.

Never use a restore procedure as a shortcut for editing completed financial history. Preserve the original backup and document why a restore was performed.
