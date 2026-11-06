<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a></p>

<p align="center">
<a href="https://github.com/laravel/framework/actions"><img src="https://github.com/laravel/framework/workflows/tests/badge.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/dt/laravel/framework" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/v/laravel/framework" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/l/laravel/framework" alt="License"></a>
</p>

## About Laravel

Laravel is a web application framework with expressive, elegant syntax. We believe development must be an enjoyable and creative experience to be truly fulfilling. Laravel takes the pain out of development by easing common tasks used in many web projects, such as:

- [Simple, fast routing engine](https://laravel.com/docs/routing).
- [Powerful dependency injection container](https://laravel.com/docs/container).
- Multiple back-ends for [session](https://laravel.com/docs/session) and [cache](https://laravel.com/docs/cache) storage.
- Expressive, intuitive [database ORM](https://laravel.com/docs/eloquent).
- Database agnostic [schema migrations](https://laravel.com/docs/migrations).
- [Robust background job processing](https://laravel.com/docs/queues).
- [Real-time event broadcasting](https://laravel.com/docs/broadcasting).

Laravel is accessible, powerful, and provides tools required for large, robust applications.

## Learning Laravel

Laravel has the most extensive and thorough [documentation](https://laravel.com/docs) and video tutorial library of all modern web application frameworks, making it a breeze to get started with the framework.

You may also try the [Laravel Bootcamp](https://bootcamp.laravel.com), where you will be guided through building a modern Laravel application from scratch.

If you don't feel like reading, [Laracasts](https://laracasts.com) can help. Laracasts contains thousands of video tutorials on a range of topics including Laravel, modern PHP, unit testing, and JavaScript. Boost your skills by digging into our comprehensive video library.

## Laravel Sponsors

We would like to extend our thanks to the following sponsors for funding Laravel development. If you are interested in becoming a sponsor, please visit the [Laravel Partners program](https://partners.laravel.com).

### Premium Partners

- **[Vehikl](https://vehikl.com/)**
- **[Tighten Co.](https://tighten.co)**
- **[WebReinvent](https://webreinvent.com/)**
- **[Kirschbaum Development Group](https://kirschbaumdevelopment.com)**
- **[64 Robots](https://64robots.com)**
- **[Curotec](https://www.curotec.com/services/technologies/laravel/)**
- **[Cyber-Duck](https://cyber-duck.co.uk)**
- **[DevSquad](https://devsquad.com/hire-laravel-developers)**
- **[Jump24](https://jump24.co.uk)**
- **[Redberry](https://redberry.international/laravel/)**
- **[Active Logic](https://activelogic.com)**
- **[byte5](https://byte5.de)**
- **[OP.GG](https://op.gg)**

## Contributing

Thank you for considering contributing to the Laravel framework! The contribution guide can be found in the [Laravel documentation](https://laravel.com/docs/contributions).

## Code of Conduct

In order to ensure that the Laravel community is welcoming to all, please review and abide by the [Code of Conduct](https://laravel.com/docs/contributions#code-of-conduct).

## Security Vulnerabilities

If you discover a security vulnerability within Laravel, please send an e-mail to Taylor Otwell via [taylor@laravel.com](mailto:taylor@laravel.com). All security vulnerabilities will be promptly addressed.

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).

## SmartDocs operational alerts

The scheduler checks document processing, database queue health, encrypted-backup freshness, archive integrity, and off-site copy presence every 15 minutes. Missing, older-than-36-hour, or unverified backups are reported in System Health and included in alerts. Configure a mailer that delivers messages and a persistent cache store before enabling alerts. The `log` and `array` mailers do not deliver email, and ephemeral cache stores cannot reliably enforce the alert cooldown across scheduled runs. The scheduled command reports these configuration problems instead of silently claiming an alert was sent.

Confirm email delivery before relying on alerts:

```
php artisan smartdocs:test-mail admin@example.org
```

Run `php artisan schedule:list` to confirm the alert task is registered. To check immediately, run `php artisan smartdocs:send-health-alerts`; it sends only when issues are present and the cooldown has expired.

## SmartDocs backups and restore

The scheduler creates an encrypted SQLite database and private-file backup daily at `config('backup.schedule')` (default `02:00`) in `storage/app/private/backups`, verifies the new archive's integrity, and removes backup files older than 365 days. The local backup directory is restricted to the application's private storage area. Backup archives use authenticated encryption derived from `APP_KEY`; keep a secure copy of that key outside the server because backups cannot be decrypted without it. Local backups do not protect against loss of the server or its storage — set `SMARTDOCS_BACKUP_OFFSITE_PATH` to an approved off-site destination so every backup is copied there after creation and verification.

Automated backup and restore currently support a file-backed SQLite database, a local private document disk, and the PHP `sqlite3`, `zip`, and `sodium` extensions. Unsupported database or storage drivers fail explicitly.

Create a backup immediately with `php artisan smartdocs:backup`. Verify a backup's integrity with `php artisan smartdocs:verify-backup [<backup>.zip.enc]` — it decrypts, extracts, and runs `PRAGMA integrity_check` on the database snapshot, then records the result. Restore is CLI-only: run `php artisan smartdocs:restore-backup <backup-filename.zip.enc>` interactively and confirm the replacement, or pass `--force` only after verifying the target and backup. Restore first creates a safety backup of the current data. Stop application and queue workers before restore, then restart them after it completes. Restore replaces both the live database and private files.

Production must run Laravel's scheduler every minute for both scheduled backups and operational alerts. See the scheduler setup above.

## APP_KEY escrow

Backups are encrypted with authenticated encryption derived from `APP_KEY`. They cannot be decrypted without it, and changing `APP_KEY` makes existing backups unrecoverable.

1. Record `php artisan smartdocs:app-key-fingerprint` (SHA-256 fingerprint; identifies the key without revealing it) with the escrow record.
2. Escrow `APP_KEY` itself offline with access controls (restricted file permissions, approved personnel only, rotation log).
3. Validate the escrowed copy matches before relying on it:
   `php artisan smartdocs:app-key-fingerprint --check` (input is hidden; compares fingerprints without displaying either key).
4. Rotation: preserve the original key securely and verify recovery implications before rotating it.

## Backup freshness and integrity monitoring

System Health (`/admin/system-health`) checks backup freshness (presence and age, older than 36 hours warns), archive integrity (verified via `php artisan smartdocs:verify-backup`), and off-site copy presence and age when `SMARTDOCS_BACKUP_OFFSITE_PATH` is configured. Unverified, failed, stale, or missing off-site backups raise operational issues and are included in alerts. File presence/age alone does not prove a backup can be restored — run verification regularly.

## Restore drill

A restore drill was performed in an isolated environment: an encrypted backup was created and verified, post-backup data loss was simulated (a canary document added and a private file tampered), and the backup was restored. Pre-restore vs post-restore snapshots matched exactly — table counts, document rows, and all private-file SHA-256 hashes — and the canary was removed. **Result: PASS.** Full record: `docs/operations.md` (restore drill section).

## Operational incident response

### Triage

1. Sign in as an administrator and review the System Health page (`/admin/system-health`). Record the time, reported status, and affected service. Do not include document contents, credentials, or secrets in incident notes.
2. Check application and scheduler logs, mail delivery, queue worker status, and recent deployment or configuration changes. `php artisan schedule:list` confirms registered tasks but does not prove that the scheduler is running.
3. For document or queue failures, inspect the processing queue and logs before retrying work. Preserve relevant logs and failed-job details for the incident lead; do not bulk-delete jobs or documents as a first response.
4. For a missing or stale backup, check that the scheduled task ran and inspect its error output. System Health reports file presence and modification time only; it does not verify that an archive can be decrypted or restored.

### Suspected account or data compromise

1. Escalate promptly to the organization’s designated security and privacy contacts and follow its notification requirements.
2. Contain access using approved infrastructure controls. An administrator may deactivate a user account through user management; preserve audit records and logs, and do not delete the account or alter evidence as a substitute for containment.
3. Have the authorized operator assess credential/session revocation and key rotation with the incident lead. Changing `APP_KEY` can make existing encrypted backups unrecoverable; preserve the original key securely and verify recovery implications before rotating it.
4. Document actions and timestamps in the organization’s incident record, limiting access to personnel who need it.

### Recovery

1. Confirm the incident lead approves recovery, choose a known backup, and verify that the original `APP_KEY` is available. If practical, validate recovery in an isolated environment first.
2. Schedule downtime. Stop application traffic and queue workers before restoring; restore replaces the live SQLite database and private document files and creates a pre-restore safety backup.
3. Use the documented CLI restore procedure. Afterward, verify application sign-in, document access, and processing; restart workers and traffic only after validation.
4. Record the selected backup, validation results, downtime, and follow-up actions. Keep an independent off-site copy because local backups do not protect against server or storage loss.

This runbook is a baseline, not a substitute for organization-specific security, privacy, legal, or disaster-recovery procedures. Test the response and restore steps before relying on them in production.

## Production transport security

Serve SmartDocs only over HTTPS. Production session cookies default to HTTPS-only; set `SESSION_SECURE_COOKIE=false` only for local HTTP development, and do not disable it in production. The application adds `X-Content-Type-Options: nosniff`, same-origin framing protection, and a strict-origin referrer policy to web responses. Configure HSTS at the HTTPS-terminating web server or trusted proxy after confirming the entire site and required subdomains are HTTPS-ready. The application intentionally does not set a Content Security Policy because its current pages use inline scripts/styles and external assets; plan and test a compatible policy before enabling one.

## Database query indexes

The document schema includes composite indexes for an employee's non-deleted library ordered by creation time and for processing-status freshness checks. Apply the migration during deployment using the normal database migration procedure. These indexes add storage and write overhead; compare query plans and latency against representative production data before adding more indexes or changing search behavior. Library searches over titles, keywords, and extracted document text use substring matching and are not accelerated by ordinary B-tree indexes; assess a database-appropriate full-text search separately if that workload becomes a bottleneck.
