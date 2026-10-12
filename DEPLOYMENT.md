# PANELCO III SmartDocs AI — Deployment Guide (Phase 10)

> OJT prototype. Production branding, data access, AI services, and server
> configuration require explicit **PANELCO III approval** before going live.

## 1. Requirements

| Component | Dev (this PC) | Production |
|---|---|---|
| PHP | 8.3+ with curl, fileinfo, mbstring, openssl, pdo_mysql, pdo_sqlite, sqlite3, zip, gd | Same, via IT-managed install |
| Composer | 2.x | Build machine only |
| Database | SQLite (dev) | **MySQL 8.x (IT-installed)** |
| Queue | `smartdocs:process` / `queue:work` manually | Queue worker as a service |
| OCR | Not installed (images fail gracefully) | **Tesseract OCR (IT-installed)** |
| AI | `none` (honest “unavailable” messages) | Approved provider + key, or approved local model |

## 2. Fresh install

```powershell
cd "C:\path\to\SmartDocs"
composer install --no-dev --optimize-autoloader
Copy-Item .env.example .env
php artisan key:generate
```

Edit `.env`:

```ini
APP_NAME="PANELCO III SmartDocs AI"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://smartdocs.your-domain.local

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=smartdocs
DB_USERNAME=smartdocs
DB_PASSWORD=***

QUEUE_CONNECTION=database
FILESYSTEM_DISK=local

# AI — only with written organizational approval (Sec. 5)
AI_PROVIDER=none
# AI_MODEL=
# AI_BASE_URL=https://api.openai.com/v1
# AI_API_KEY=

# OCR engine path (IT-installed Tesseract), blank = auto-detect
# OCR_BINARY=
```

```powershell
php artisan migrate --force
php artisan db:seed --force   # creates starter categories/departments/demo users
```

**Immediately** change or delete the demo passwords
(`admin/manager/employee@panelco3.local` / `Password123!`) and create real
accounts under Admin → User Management.

## 3. Storage & backups

- Originals live in `storage/app/private/documents` — **never** under `public/`.
  The `local` disk has `serve => false`; downloads go only through
  authenticated, policy-checked routes.
- Back up nightly: database dump + `storage/app/private` snapshot.
- Restore test: restore both to a staging copy and run
  `php artisan test` before trusting a backup.

## 4. Queue worker (required for automatic processing)

Uploads queue a `ProcessDocument` job. Without a worker they stay `pending`.

Windows (Task Scheduler, every minute):

```powershell
php C:\path\to\SmartDocs\artisan queue:work --once --tries=1
```

or the synchronous fallback (no worker needed):

```powershell
php artisan smartdocs:process --limit=20
```

Linux: supervise `php artisan queue:work --sleep=3 --tries=1` with systemd.

## 5. AI provider approval checklist

1. Written approval to send document excerpts to the provider (or decision
   to use an approved **local** model so data stays on-network).
2. Set `AI_PROVIDER=openai-compatible`, `AI_MODEL`, `AI_BASE_URL`, `AI_API_KEY`
   in server `.env` only — never in git, never in frontend JS.
3. Verify: upload a test doc → summary appears; disable the key →
   system reports “temporarily unavailable” instead of fabricating.
4. Confirm retrieval still filters by user permission **before** the model
   sees anything (enforced in `DocumentRetrievalService`, not in prompts).

## 6. OCR (Tesseract)

1. IT installs Tesseract; set `OCR_BINARY` to its full path (or leave blank
   if `tesseract` is on PATH).
2. Verify: upload a scanned PNG → completes with page-1 chunk; without the
   binary it must fail gracefully (“upload a readable copy”).

## 7. Security checklist (pre-launch)

- [ ] `APP_DEBUG=false`, `APP_ENV=production`
- [ ] Public registration stays disabled (`Auth::routes(['register' => false])`)
- [ ] `storage/app/private` not web-served; no direct file URLs anywhere
- [ ] Chat/upload routes throttled; uploads capped at 20 MB with MIME allowlist
- [ ] Audit logs reviewed (Admin → Audit Logs); no contents/secrets in logs
- [ ] Demo accounts removed or re-credentialed; roles assigned by department
- [ ] Retention/deletion policy signed off by the organization

## 8. Test plan (spec §9 → commands)

```powershell
php artisan test   # 44 tests covering scenarios below
```

| # | Scenario | Covered by |
|---|---|---|
| 1 | Valid PDF processes | Phase4ExtractionTest |
| 2 | DOCX extracts | Phase4ExtractionTest |
| 3 | Scanned PDF via OCR (needs engine) | Phase4ExtractionTest (graceful path) + manual |
| 4 | Corrupt/unsupported rejected or failed | Phase3/Phase4 tests |
| 5 | Empty library message | Phase6/Phase7 tests |
| 6 | Unrelated question → no-match | Phase6/Phase7 tests |
| 7 | Relevant question → cited answer | Phase6/Phase7 tests |
| 8 | Citation opens correct doc + highlight | Manual: chat → source link |
| 9 | Cross-department blocked | Phase2/Phase3/Phase6 tests |
| 10 | Failure writes no false summary | Phase8SecurityTest |
| 11 | AI down → clear error | Phase5AiTest + manual key removal |
| 12 | DB error ≠ empty result | Phase8SecurityTest (+ code review) |
| 13 | Malicious instructions ignored | Phase8SecurityTest |
| 14 | Reprocess → no duplicate chunks | Phase4ExtractionTest |
| 15 | Delete/revoke → excluded | Phase8SecurityTest |

## 9. Hand-over to IT

Request: (a) MySQL 8.x database + credentials, (b) Tesseract install,
(c) decision + key for the AI provider (or local-model server),
(d) backup schedule, (e) sign-off on branding, categories, and retention.
