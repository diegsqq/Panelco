# OnlyOffice Document Server (P-ARCHIVE)

Free, self-hosted Word-style editor for `/library/{id}/content/edit`.

## 1. Install Docker Desktop

Docker CLI is not currently installed on this machine. Install Docker Desktop, then confirm:

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
ONLYOFFICE_URL=http://127.0.0.1:8080
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

Click **OnlyOffice** in the title bar. Saves are converted back into the P-ARCHIVE content editor fields. The original `.docx` is opened directly when the document has no edited content yet.

## Notes

- No AI API key is involved. This is document editing only.
- The Document Server callback is protected by a signed token.
- Browser must be able to reach `http://127.0.0.1:8080`.
- Container must be able to reach `http://host.docker.internal:8000`.
- Stop with `docker compose -f docker-compose.onlyoffice.yml down`.
