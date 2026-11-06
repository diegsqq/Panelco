<?php

namespace App\Console\Commands;

use App\Services\LocalBackupService;
use Illuminate\Console\Command;
use Throwable;

class RestoreLocalBackup extends Command
{
    protected $signature = 'smartdocs:restore-backup {backup : Encrypted backup filename in private backup storage} {--force : Confirm that live database and private files will be replaced}';

    protected $description = 'Restore the SQLite database and private files from an encrypted local backup';

    public function handle(LocalBackupService $backups): int
    {
        $filename = (string) $this->argument('backup');
        if ($filename !== basename($filename)) {
            $this->error('Provide only a backup filename, not a path.');

            return self::FAILURE;
        }

        if (! $this->option('force')) {
            $this->components->warn('This replaces the live database and private files. A pre-restore backup will be created first.');
            if (! $this->input->isInteractive() || ! $this->confirm('Continue with restore?', false)) {
                $this->error('Restore cancelled. Re-run interactively or pass --force after reviewing the target.');

                return self::FAILURE;
            }
        }

        try {
            $backups->restore($backups->backupDirectory().DIRECTORY_SEPARATOR.$filename);
        } catch (Throwable $exception) {
            report($exception);
            $this->error('SmartDocs restore failed: '.$exception->getMessage());

            return self::FAILURE;
        }

        $this->info('SmartDocs database and private files were restored. Restart application workers before resuming traffic.');

        return self::SUCCESS;
    }
}
