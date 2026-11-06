<?php

return [
    'path' => env('SMARTDOCS_BACKUP_PATH', storage_path('app/private/backups')),
    // Optional second copy destination (a different server, NAS, or mounted
    // off-site volume). When set, every backup is copied there after it is
    // created and verified, and System Health checks the copy's presence and age.
    'offsite_path' => env('SMARTDOCS_BACKUP_OFFSITE_PATH'),
    'retention_days' => 365,
    'schedule' => '02:00',
];
