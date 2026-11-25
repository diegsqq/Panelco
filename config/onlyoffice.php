<?php

return [
    // Set ONLYOFFICE_ENABLED=true once the Document Server is running.
    'enabled' => env('ONLYOFFICE_ENABLED', false),

    // Browser-facing Document Server URL (used to load docsapi.js).
    'url' => rtrim((string) env('ONLYOFFICE_URL', 'http://127.0.0.1:8080'), '/'),

    // URL the Document Server container uses to reach this Laravel app.
    // On Docker Desktop use host.docker.internal.
    'internal_url' => rtrim((string) env('ONLYOFFICE_INTERNAL_URL', env('APP_URL', 'http://127.0.0.1:8000')), '/'),

    // Must match JWT_SECRET in docker-compose.onlyoffice.yml.
    'jwt_secret' => (string) env('ONLYOFFICE_JWT_SECRET', ''),

    'jwt_header' => (string) env('ONLYOFFICE_JWT_HEADER', 'Authorization'),
];
