<?php

namespace App\Console\Commands;

use App\Services\LocalBackupService;
use Illuminate\Console\Command;
use Throwable;

class VerifyLocalBackup extends Command
{
    protected $signature = 'smartdocs:verify-backup {backup? : Encrypted backup filename in private backup storage; defaults to the newest backup}';

    protected $description = 'Decrypt, extract, and integrity-check an encrypted backup and record the verification result';

    public function handle(LocalBackupService $backups): int
    {
        $filename = (string) $this->argument('backup');
        if ($filename === '') {
            $files = glob($backups->backupDirectory().DIRECTORY_SEPARATOR.'smartdocs-*.zip.enc') ?: [];
            $newest = null;
            $newestTime = null;
            foreach ($files as $file) {
                if (is_link($file) || ! is_file($file)) {
                    continue;
                }
                $modified = filemtime($file);
                if ($modified !== false && ($newestTime === null || $modified > $newestTime)) {
                    $newest = $file;
                    $newestTime = $modified;
                }
            }

            if ($newest === null) {
                $this->error('No encrypted backup was found to verify.');

                return self::FAILURE;
            }
        } elseif ($filename !== basename($filename)) {
            $this->error('Provide only a backup filename, not a path.');

            return self::FAILURE;
        } else {
            $newest = $backups->backupDirectory().DIRECTORY_SEPARATOR.$filename;
        }

        try {
            $result = $backups->verify($newest);
        } catch (Throwable $exception) {
            report($exception);
            $this->error('Backup verification failed: '.$exception->getMessage());

            return self::FAILURE;
        }

        if ($result['ok'] !== true) {
            $this->error('Backup integrity verification failed for '.basename($newest).': '.$result['detail']);

            return self::FAILURE;
        }

        $this->info('Backup integrity verified: '.basename($newest));
        $this->line($result['detail']);

        return self::SUCCESS;
    }
}
