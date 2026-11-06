<?php

namespace App\Console\Commands;

use App\Services\LocalBackupService;
use Illuminate\Console\Command;
use Throwable;

class CreateLocalBackup extends Command
{
    protected $signature = 'smartdocs:backup';

    protected $description = 'Create an encrypted local backup of the SQLite database and private files';

    public function handle(LocalBackupService $backups): int
    {
        try {
            $path = $backups->create();
        } catch (Throwable $exception) {
            report($exception);
            $this->error('SmartDocs backup failed: '.$exception->getMessage());

            return self::FAILURE;
        }

        $this->info('Encrypted SmartDocs backup created and integrity-verified: '.basename($path));
        if (config('backup.offsite_path')) {
            $this->line('Off-site copy written to the configured destination.');
        } else {
            $this->line('No off-site destination configured; set SMARTDOCS_BACKUP_OFFSITE_PATH so backups survive server or storage loss.');
        }

        return self::SUCCESS;
    }
}
