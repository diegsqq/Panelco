## SmartDocs scheduled jobs

Production must run Laravel's scheduler every minute so both scheduled tasks execute:

```
* * * * * cd /path/to/SmartDocs && php artisan schedule:run >> /var/log/smartdocs/schedule.log 2>&1
```

- `smartdocs:backup` — daily at `config('backup.schedule')` (default `02:00`),
  creates an encrypted SQLite + private-files backup in
  `storage/app/private/backups`, verifies its integrity, copies it to the
  off-site destination when `SMARTDOCS_BACKUP_OFFSITE_PATH` is set, and prunes
  copies older than 365 days.
- `smartdocs:send-health-alerts` — every 15 minutes; emails active
  administrators a rate-limited summary of processing and backup issues.

Verify registration with `php artisan schedule:list`. The scheduler itself must
be supervised (systemd timer, cron, or Task Scheduler) — `schedule:list` only
lists tasks, it does not prove the scheduler is running.

## Persistent cache

Operational alerts use `Cache::lock(...)` and a rate-limit key, so `cache.default`
must not be `array`, `null`, or `octane` in production. Set `CACHE_STORE=file`,
`database`, `redis`, or `memcached` in `.env` and confirm
`php artisan smartdocs:send-health-alerts` does not report a missing persistent
cache store.

## Email delivery provider

Operational alerts cannot be delivered by the `log` or `array` mailers.
Configure a delivery mailer in `.env`, for example:

```
MAIL_MAILER=smtp
MAIL_HOST=smtp.example.org
MAIL_PORT=587
MAIL_USERNAME=...
MAIL_PASSWORD=...
MAIL_FROM_ADDRESS=alerts@example.org
MAIL_FROM_NAME="PANELCO III SmartDocs AI"
```

Confirm delivery before enabling alerts:

```
php artisan smartdocs:test-mail admin@example.org
```

A mail log (`php artisan test` uses the `array` mailer) is sufficient for local
development but must not be used in production.

## Off-site backup copies

Set `SMARTDOCS_BACKUP_OFFSITE_PATH` to a different server, NAS, or mounted
off-site volume. Every backup is copied there after creation and verified; the
destination must differ from the local backup, document, and public directories.
System Health reports the off-site copy's presence and age; a missing or stale
copy raises an operational issue.

## APP_KEY escrow

Backups are encrypted with authenticated encryption derived from `APP_KEY`.
Backups cannot be decrypted without it, and changing `APP_KEY` makes existing
backups unrecoverable.

1. Record `php artisan smartdocs:app-key-fingerprint` (SHA-256 fingerprint;
   identifies the key without revealing it) with the escrow record.
2. Escrow `APP_KEY` itself offline with access controls (restricted file
   permissions, approved personnel only, rotation log).
3. Validate the escrowed copy matches before relying on it:
   `php artisan smartdocs:app-key-fingerprint --check` (input is hidden;
   compares fingerprints without displaying either key).
4. Rotation: preserve the original key securely and verify recovery implications
   before rotating; any backup made under the old key needs the old key.

## Backup freshness and integrity monitoring

System Health (`/admin/system-health`) and operational alerts now check:

- Freshness: latest backup presence and age (older than 36 hours warns).
- Integrity: latest backup verified with `php artisan smartdocs:verify-backup`
  (decrypt, extract, `PRAGMA integrity_check` on the database snapshot);
  unverified or failed backups warn.
- Off-site copy: presence and age of the copy at `SMARTDOCS_BACKUP_OFFSITE_PATH`
  when configured.

Run `php artisan smartdocs:backup` and `php artisan smartdocs:verify-backup`
regularly; do not rely on file presence/age alone.

## Restore drill

A restore drill was performed in an isolated environment:

- Created an encrypted backup, verified it, then simulated post-backup data loss
  (added a canary document and tampered a private file).
- Restored from the backup; compared pre-restore vs post-restore snapshots:
  table counts, document rows, and all private-file SHA-256 hashes matched
  exactly. The canary was removed; the tampered file was restored.
- **Result: PASS.** Full record: `RESTORE_DRILL_RECORD.md`.
