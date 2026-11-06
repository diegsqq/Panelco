<?php

namespace Tests\Feature;

use App\Mail\OperationalHealthAlert;
use App\Models\Document;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Tests\TestCase;

class OperationalHealthAlertsTest extends TestCase
{
    use RefreshDatabase;

    private string $backupPath;

    protected function setUp(): void
    {
        parent::setUp();

        $this->backupPath = storage_path('framework/testing-backups/operational-alerts-'.Str::uuid());
        config([
            'cache.default' => 'file',
            'cache.stores.file.path' => storage_path('framework/testing-cache/operational-alerts'),
            'mail.default' => 'smtp',
            'queue.default' => 'database',
            'backup.path' => $this->backupPath,
        ]);
        File::ensureDirectoryExists($this->backupPath);
        File::put($this->backupPath.DIRECTORY_SEPARATOR.'smartdocs-test.zip.enc', 'test');
        $this->writeVerificationRecord('smartdocs-test.zip.enc', true);
        Cache::flush();
    }

    private function writeVerificationRecord(string $filename, bool $ok): void
    {
        File::put($this->backupPath.DIRECTORY_SEPARATOR.'verification.json', json_encode([
            $filename => [
                'ok' => $ok,
                'verified_at' => now()->toIso8601String(),
                'detail' => 'test fixture backup',
            ],
        ]));
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->backupPath);

        parent::tearDown();
    }

    public function test_operational_issues_are_emailed_to_active_administrators_and_rate_limited(): void
    {
        Mail::fake();
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN, 'is_active' => true]);
        $inactiveAdmin = User::factory()->create(['role' => User::ROLE_ADMIN, 'is_active' => false]);
        User::factory()->create(['role' => User::ROLE_EMPLOYEE, 'is_active' => true]);
        $this->createDocument($admin, 'failed', 'Failed document');
        $stuck = $this->createDocument($admin, 'processing', 'Stuck document');
        $stuck->forceFill(['updated_at' => now()->subMinutes(31)])->saveQuietly();
        DB::table('jobs')->insert([
            [
                'queue' => 'default',
                'payload' => '{}',
                'attempts' => 0,
                'reserved_at' => now()->subMinutes(31)->timestamp,
                'available_at' => now()->subMinutes(31)->timestamp,
                'created_at' => now()->subMinutes(31)->timestamp,
            ],
            [
                'queue' => 'default',
                'payload' => '{}',
                'attempts' => 0,
                'reserved_at' => null,
                'available_at' => now()->subMinutes(31)->timestamp,
                'created_at' => now()->subMinutes(31)->timestamp,
            ],
        ]);
        DB::table('failed_jobs')->insert([
            'uuid' => (string) Str::uuid(),
            'connection' => 'database',
            'queue' => 'default',
            'payload' => '{}',
            'exception' => 'sensitive exception details',
            'failed_at' => now(),
        ]);

        $this->assertSame(0, Artisan::call('smartdocs:send-health-alerts'));
        $this->assertSame(0, Artisan::call('smartdocs:send-health-alerts'));

        Mail::assertSent(OperationalHealthAlert::class, 1);
        Mail::assertSent(OperationalHealthAlert::class, function (OperationalHealthAlert $mail) use ($admin, $inactiveAdmin) {
            return $mail->hasTo($admin->email)
                && ! $mail->hasTo($inactiveAdmin->email)
                && $mail->issues === [
                    'Failed documents' => 1,
                    'Documents stuck in processing for over 30 minutes' => 1,
                    'Queue jobs reserved for over 30 minutes' => 1,
                    'Queue jobs overdue for over 30 minutes' => 1,
                    'Failed queue jobs' => 1,
                ];
        });
        $this->assertNotSame('', Cache::get('smartdocs:operational-health-alerts:last-sent'));
    }

    public function test_no_alert_is_sent_when_only_recent_or_no_issues_exist(): void
    {
        Mail::fake();
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $this->createDocument($admin, 'processing', 'Recently processing');
        DB::table('jobs')->insert([
            'queue' => 'default',
            'payload' => '{}',
            'attempts' => 0,
            'reserved_at' => null,
            'available_at' => now()->timestamp,
            'created_at' => now()->timestamp,
        ]);

        $this->assertSame(0, Artisan::call('smartdocs:send-health-alerts'));
        Mail::assertNothingSent();
        $this->assertNull(Cache::get('smartdocs:operational-health-alerts:last-sent'));
    }

    public function test_missing_or_stale_backup_triggers_an_operational_alert(): void
    {
        Mail::fake();
        User::factory()->create(['role' => User::ROLE_ADMIN]);
        $backupFile = $this->backupPath.DIRECTORY_SEPARATOR.'smartdocs-test.zip.enc';
        File::delete($backupFile);

        $this->assertSame(0, Artisan::call('smartdocs:send-health-alerts'));
        Mail::assertSent(OperationalHealthAlert::class, function (OperationalHealthAlert $mail) {
            return count(array_filter(
                array_keys($mail->issues),
                fn (string $issue) => str_contains($issue, 'No encrypted local backup was found')
            )) === 1;
        });

        Cache::forget('smartdocs:operational-health-alerts:last-sent');
        File::put($backupFile, 'test');
        touch($backupFile, now()->subHours(37)->getTimestamp());

        $this->assertSame(0, Artisan::call('smartdocs:send-health-alerts'));
        Mail::assertSent(OperationalHealthAlert::class, 2);
        Mail::assertSent(OperationalHealthAlert::class, function (OperationalHealthAlert $mail) {
            return count(array_filter(
                array_keys($mail->issues),
                fn (string $issue) => str_contains($issue, '37 hours old')
            )) === 1;
        });
    }

    public function test_alert_command_fails_explicitly_when_mailer_cannot_deliver(): void
    {
        Mail::fake();
        config(['mail.default' => 'log']);
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $this->createDocument($admin, 'failed', 'Failed document');

        $this->assertSame(1, Artisan::call('smartdocs:send-health-alerts'));
        $this->assertStringContainsString('configure a delivery mailer', Artisan::output());
        Mail::assertNothingSent();
    }

    public function test_alert_command_requires_a_persistent_cache_store(): void
    {
        config(['cache.default' => 'array']);
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $this->createDocument($admin, 'failed', 'Failed document');

        $this->assertSame(1, Artisan::call('smartdocs:send-health-alerts'));
        $this->assertStringContainsString('require a persistent cache store', Artisan::output());
    }

    private function createDocument(User $uploader, string $status, string $title): Document
    {
        return Document::create([
            'uploaded_by' => $uploader->id,
            'title' => $title,
            'original_filename' => Str::slug($title).'.txt',
            'storage_path' => 'documents/'.Str::slug($title).'.txt',
            'mime_type' => 'text/plain',
            'file_size' => 10,
            'processing_status' => $status,
        ]);
    }
}
