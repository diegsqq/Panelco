# SmartDocs Restore Drill Record

**Date:** 2026-11-06  
**Environment:** Isolated copy of the production application under
`C:\Users\PanelcoIII\AppData\Local\Temp\opencode\smartdocs-restore-drill\app`
(safe environment; no production traffic, no production data mutation).  
**Backup restored:** `smartdocs-20261106-022350-1f0f94b7-3222-4898-974c-11e8a57f8d31.zip.enc`
(encrypted with the deployment's APP_KEY; stored in `storage/app/private/backups`).

## Purpose

Verify that an encrypted backup can be decrypted, integrity-checked, and
restored so that the live SQLite database and private document files are
recovered exactly as they were at backup time — including undoing changes
made after the backup was taken.

## Prerequisites verified before restore

1. `APP_KEY` is configured and valid for encrypted backups (`sodium` extension
   loaded; key length matches `SODIUM_CRYPTO_SECRETBOX_KEYBYTES`).
2. `php artisan smartdocs:verify-backup` on the target backup returned OK — the
   archive decrypted, extracted, and the database snapshot passed
   `PRAGMA integrity_check`. Verification is recorded in `verification.json`.
3. Application traffic and queue workers were stopped in the drill environment
   (isolated copy; no external traffic).
4. The restore target was confirmed against the backup filename before running.

## Steps performed

| Step | Command / action | Result |
|------|------------------|--------|
| 1 | `php drill_snapshot.php pre.json` — captured table counts, document rows, and SHA-256 hashes of all 61 private files | Pre-restore baseline written |
| 2 | `php artisan smartdocs:backup` — created the encrypted backup | Created; integrity-verified automatically during create |
| 3 | `php artisan smartdocs:verify-backup` — re-verified the backup | OK |
| 4 | `php drill_mutate.php` — simulated post-backup data loss: added a canary document ("RESTORE DRILL CANARY") and tampered one existing private file | Documents: 53 → 54; 1 file tampered |
| 5 | `php drill_snapshot.php post-mutate.json` — captured mutated state | Confirmed mutation |
| 6 | `php artisan smartdocs:restore-backup <backup>.zip.enc --force` — restored database + private files (restore first created a pre-restore safety backup) | Success |
| 7 | `php drill_snapshot.php post-restore.json` — captured post-restore state | Documents back to 53; 61 files |
| 8 | `php drill_compare.php pre.json post-restore.json` — compared pre-restore vs post-restore snapshots | All match (see below) |

## Outcome

**DRILL_RESULT: PASS**

- Table counts match pre-restore exactly:
  users=2, departments=8, document_categories=9, documents=53,
  document_chunks=1032, audit_logs=233.
- All 53 document rows match pre-restore (titles, storage paths, processing
  statuses).
- All 61 private document file SHA-256 hashes match pre-restore — including
  the tampered file, which was restored to its backed-up content.
- The post-backup canary document was removed, confirming the restore replaces
  (not merges with) current data.

## Notes for the deployment team

- Restore is CLI-only and destructive: it replaces the live SQLite database and
  private document directory. Always stop application traffic and queue workers
  before running, and restart them after validation.
- Restore first creates a safety backup of the current data in the backup
  directory.
- Decryption requires the original `APP_KEY`; keep an escrowed copy offline and
  validate it with `php artisan smartdocs:app-key-fingerprint --check` before
  relying on it for recovery.
- Backups are created locally only until `SMARTDOCS_BACKUP_OFFSITE_PATH` is
  configured; local copies do not protect against server or storage loss.
- Integrity verification (`php artisan smartdocs:verify-backup`) should be run
  after every backup and checked on System Health (`/admin/system-health`) —
  freshness checks alone do not prove a backup can be restored.

## Drill artifacts (drill environment only, not production)

- `pre.json` — pre-restore snapshot
- `post-mutate.json` — simulated post-backup corruption snapshot
- `post-restore.json` — post-restore snapshot
- `drill_snapshot.php`, `drill_mutate.php`, `drill_compare.php` — helpers used
  in the drill
