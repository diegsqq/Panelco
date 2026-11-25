# OnlyOffice Document Server (P-ARCHIVE)

Free, self-hosted Word-style editor for `/library/{id}/content/edit`.

## 1. Install Docker Desktop

Docker Desktop is installed on this machine. If Windows virtualization features were just enabled, restart Windows before continuing. Then confirm:

```powershell
docker --version
docker compose version
```

## 2. Generate a JWT secret

```powershell
php -r "echo bin2hex(random_bytes(32));"
```

## 3. Configure `.env`

```dotenv
ONLYOFFICE_ENABLED=true
ONLYOFFICE_URL=http://127.0.0.1:8081
ONLYOFFICE_INTERNAL_URL=http://host.docker.internal:8000
ONLYOFFICE_JWT_SECRET=<the secret from step 2>
```

`ONLYOFFICE_URL` is what your browser uses. `ONLYOFFICE_INTERNAL_URL` is what the Document Server container uses to reach Laravel. On Docker Desktop for Windows/Mac, `host.docker.internal` works.

## 4. Start Laravel reachable from the container

```powershell
php artisan config:clear
php artisan serve --host=0.0.0.0 --port=8000
```

## 5. Start Document Server

The same `ONLYOFFICE_JWT_SECRET` must appear in `.env` and in Compose.

```powershell
docker compose -f docker-compose.onlyoffice.yml up -d
```

## 6. Use it

Open:

```text
http://127.0.0.1:8000/library/71/content/edit
```

This route opens OnlyOffice directly. Saves are converted back into the P-ARCHIVE content fields. The original `.docx` is opened directly when the document has no edited content yet.

The **Ask Lightning AI** button opens a right-side P-ARCHIVE AI panel inside the editor. It calls the document ask endpoint and cites authorized sources. OnlyOffice's own collaboration chat is disabled.

**Open in editor** in the library detail panel uses this same route. The library row dropdown **View** action uses `/library/{id}/content/edit?view=1`, which opens OnlyOffice in read-only mode.

## Notes

- The editor theme follows the P-ARCHIVE user theme: light uses `theme-white`, dark uses `theme-night`.
- Document editing needs no AI key. The Ask Lightning AI panel uses the app's existing AI env settings.
- The Document Server callback is protected by a signed token.
- Port 8080 is occupied by Windows, so Document Server uses 8081.
- Browser must be able to reach `http://127.0.0.1:8081`.
- Container must be able to reach `http://host.docker.internal:8000`.
- Stop with `docker compose -f docker-compose.onlyoffice.yml down`.
