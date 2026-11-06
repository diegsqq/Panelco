<?php

namespace Tests\Feature;

use App\Services\LocalBackupService;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class BackupIntegrityMonitoringTest extends TestCase
{
    private string $testDirectory;

    private string $databasePath;

    private string $documentsPath;

    private string $backupPath;

    private string $offsitePath;

    protected function setUp(): void
    {
        parent::setUp();

        $this->testDirectory = storage_path('app/private/testing-integrity-'.Str::uuid());
        $this->databasePath = $this->testDirectory.DIRECTORY_SEPARATOR.'database.sqlite';
        $this->documentsPath = $this->testDirectory.DIRECTORY_SEPARATOR.'documents';
        $this->backupPath = $this->testDirectory.DIRECTORY_SEPARATOR.'backups';
        $this->offsitePath = $this->testDirectory.DIRECTORY_SEPARATOR.'offsite';
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
            'backup.offsite_path' => null,
            'backup.retention_days' => 365,
        ]);
        DB::purge('sqlite');
        DB::connection('sqlite')->statement('CREATE TABLE records (id INTEGER PRIMARY KEY, value TEXT NOT NULL)');
        DB::connection('sqlite')->table('records')->insert(['value' => 'integrity source value']);
        file_put_contents($this->documentsPath.DIRECTORY_SEPARATOR.'report.txt', 'integrity source document');
    }

    protected function tearDown(): void
    {
        DB::purge('sqlite');
        $this->removeTestDirectory($this->testDirectory);

        parent::tearDown();
    }

    public function test_created_backups_are_verified_automatically_and_reported_as_healthy(): void
    {
        $path = app(LocalBackupService::class)->create();

        $records = app(LocalBackupService::class)->verificationRecords();
        $this->assertTrue($records[basename($path)]['ok'] ?? false);

        $health = app(LocalBackupService::class)->health();
        $this->assertSame('ok', $health['status']);
        $this->assertSame('ok', $health['integrity']['status']);
        $this->assertNotNull($health['integrity_verified_at']);
        $this->assertStringContainsString('Archive integrity verified at', $health['integrity']['detail']);
    }

    public function test_latest_backup_without_verification_is_reported_as_needing_attention(): void
    {
        $file = $this->backupPath.DIRECTORY_SEPARATOR.'smartdocs-unverified.zip.enc';
        file_put_contents($file, 'not-a-real-backup');
        touch($file, now()->getTimestamp());

        $health = app(LocalBackupService::class)->health();
        $this->assertSame('warning', $health['status']);
        $this->assertSame('warning', $health['integrity']['status']);
        $this->assertStringContainsString('has not been integrity-verified', $health['integrity']['detail']);
    }

    public function test_failed_verification_is_reported_with_its_reason(): void
    {
        $file = $this->backupPath.DIRECTORY_SEPARATOR.'smartdocs-failed.zip.enc';
        file_put_contents($file, 'corrupt');
        file_put_contents($this->backupPath.DIRECTORY_SEPARATOR.'verification.json', json_encode([
            basename($file) => ['ok' => false, 'verified_at' => now()->toIso8601String(), 'detail' => 'The backup archive is incomplete.'],
        ]));

        $health = app(LocalBackupService::class)->health();
        $this->assertSame('warning', $health['status']);
        $this->assertStringContainsString('Backup integrity verification failed', $health['integrity']['detail']);
        $this->assertStringContainsString('The backup archive is incomplete.', $health['integrity']['detail']);
    }

    public function test_verify_command_detects_a_corrupt_backup_and_records_the_failure(): void
    {
        $backups = app(LocalBackupService::class);
        $path = $backups->create();
        $contents = file_get_contents($path);
        $contents[40] = chr(ord($contents[40]) ^ 0xFF);
        file_put_contents($path, $contents);

        $this->assertSame(1, Artisan::call('smartdocs:verify-backup', ['backup' => basename($path)]));
        $this->assertStringContainsString('integrity verification failed', Artisan::output());

        $records = $backups->verificationRecords();
        $this->assertFalse($records[basename($path)]['ok']);

        $health = $backups->health();
        $this->assertSame('warning', $health['status']);
        $this->assertSame('warning', $health['integrity']['status']);
    }

    public function test_verify_command_succeeds_for_an_untouched_backup(): void
    {
        $path = app(LocalBackupService::class)->create();

        $this->assertSame(0, Artisan::call('smartdocs:verify-backup', ['backup' => basename($path)]));
        $this->assertStringContainsString('Backup integrity verified', Artisan::output());
    }

    public function test_offsite_copies_are_created_and_missing_offsite_copies_are_reported(): void
    {
        config(['backup.offsite_path' => $this->offsitePath]);
        $path = app(LocalBackupService::class)->create();

        $copy = $this->offsitePath.DIRECTORY_SEPARATOR.basename($path);
        $this->assertFileExists($copy);

        $health = app(LocalBackupService::class)->health();
        $this->assertSame('ok', $health['status']);
        $this->assertTrue($health['offsite']['configured']);
        $this->assertSame('ok', $health['offsite']['status']);

        unlink($copy);
        $health = app(LocalBackupService::class)->health();
        $this->assertSame('warning', $health['status']);
        $this->assertSame('warning', $health['offsite']['status']);
        $this->assertStringContainsString('off-site copy of the latest backup is missing', $health['offsite']['detail']);
    }

    public function test_offsite_destination_must_differ_from_the_backup_directory(): void
    {
        config(['backup.offsite_path' => $this->backupPath]);

        $this->assertSame(1, Artisan::call('smartdocs:backup'));
        $this->assertStringContainsString('off-site backup destination must differ', Artisan::output());
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
