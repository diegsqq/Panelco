<?php

namespace Tests\Feature;

use App\Services\LocalBackupService;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class LocalBackupTest extends TestCase
{
    private string $testDirectory;

    private string $databasePath;

    private string $documentsPath;

    private string $backupPath;

    protected function setUp(): void
    {
        parent::setUp();

        $this->testDirectory = storage_path('app/private/testing-backup-'.Str::uuid());
        $this->databasePath = $this->testDirectory.DIRECTORY_SEPARATOR.'database.sqlite';
        $this->documentsPath = $this->testDirectory.DIRECTORY_SEPARATOR.'documents';
        $this->backupPath = $this->testDirectory.DIRECTORY_SEPARATOR.'backups';
        mkdir($this->testDirectory, 0700, true);
        mkdir($this->documentsPath, 0700);
        mkdir($this->backupPath, 0700);
        touch($this->databasePath);

        config([
            'database.default' => 'sqlite',
            'database.connections.sqlite.database' => $this->databasePath,
            'filesystems.disks.private.driver' => 'local',
            'filesystems.disks.private.root' => $this->documentsPath,
            'backup.path' => $this->backupPath,
            'backup.retention_days' => 365,
        ]);
        DB::purge('sqlite');
        DB::connection('sqlite')->statement('CREATE TABLE records (id INTEGER PRIMARY KEY, value TEXT NOT NULL)');
        DB::connection('sqlite')->table('records')->insert(['value' => 'original database value']);
        file_put_contents($this->documentsPath.DIRECTORY_SEPARATOR.'report.txt', 'original private document');
    }

    protected function tearDown(): void
    {
        DB::purge('sqlite');
        $this->removeTestDirectory($this->testDirectory);

        parent::tearDown();
    }

    public function test_backup_is_encrypted_and_contains_database_and_private_documents(): void
    {
        $this->assertSame(0, Artisan::call('smartdocs:backup'));
        $files = glob($this->backupPath.DIRECTORY_SEPARATOR.'smartdocs-*.zip.enc');
        $this->assertCount(1, $files);
        $contents = file_get_contents($files[0]);
        $this->assertStringStartsWith('P3SB1', $contents);
        $this->assertStringNotContainsString('original private document', $contents);
        $this->assertFileExists($files[0]);
    }

    public function test_restore_requires_cli_flag_and_restores_database_and_private_files(): void
    {
        $backups = app(LocalBackupService::class);
        $backup = $backups->create();
        DB::table('records')->update(['value' => 'changed live value']);
        file_put_contents($this->documentsPath.DIRECTORY_SEPARATOR.'report.txt', 'changed private document');

        $this->assertSame(1, Artisan::call('smartdocs:restore-backup', [
            'backup' => basename($backup),
        ]));
        $this->assertStringContainsString('Restore cancelled', Artisan::output());
        $this->assertSame('changed live value', DB::table('records')->value('value'));

        $this->assertSame(0, Artisan::call('smartdocs:restore-backup', [
            'backup' => basename($backup),
            '--force' => true,
        ]));
        $this->assertSame('original database value', DB::table('records')->value('value'));
        $this->assertSame('original private document', file_get_contents($this->documentsPath.DIRECTORY_SEPARATOR.'report.txt'));
        $this->assertCount(2, glob($this->backupPath.DIRECTORY_SEPARATOR.'smartdocs-*.zip.enc'));
    }

    public function test_expired_backups_are_pruned_and_invalid_destinations_fail(): void
    {
        $expired = $this->backupPath.DIRECTORY_SEPARATOR.'smartdocs-expired.zip.enc';
        file_put_contents($expired, 'old');
        touch($expired, now()->subDays(366)->timestamp);

        $this->app['config']->set('backup.path', public_path('unsafe-backups'));
        $this->assertSame(1, Artisan::call('smartdocs:backup'));
        $this->assertStringContainsString('separate from the database and document directory', Artisan::output());
        $this->assertFileExists($expired);

        $this->app['config']->set('backup.path', $this->backupPath);
        $this->assertSame(0, Artisan::call('smartdocs:backup'));
        $this->assertFileDoesNotExist($expired);
    }

    public function test_restore_rejects_backup_encrypted_with_a_different_application_key(): void
    {
        $backups = app(LocalBackupService::class);
        $backup = $backups->create();
        config(['app.key' => 'base64:'.base64_encode(random_bytes(32))]);

        $this->assertSame(1, Artisan::call('smartdocs:restore-backup', [
            'backup' => basename($backup),
            '--force' => true,
        ]));
        $this->assertStringContainsString('original APP_KEY', Artisan::output());
        $this->assertSame('original database value', DB::table('records')->value('value'));
        $this->assertSame('original private document', file_get_contents($this->documentsPath.DIRECTORY_SEPARATOR.'report.txt'));
    }

    private function removeTestDirectory(string $path): void
    {
        if (! is_dir($path)) {
            return;
        }
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($path, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );
        foreach ($iterator as $entry) {
            $entry->isDir() ? rmdir($entry->getPathname()) : unlink($entry->getPathname());
        }
        rmdir($path);
    }
}
