<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Document;
use App\Services\Ai\AiProvider;
use App\Services\LocalBackupService;
use App\Support\SystemHealthThresholds;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;
use Throwable;

class SystemHealthController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'role:admin']);
    }

    public function index(): View
    {
        $databaseAvailable = false;
        try {
            DB::select('SELECT 1');
            $databaseAvailable = true;
            $database = ['status' => 'ok', 'detail' => 'Database connection is responding.'];
        } catch (Throwable $exception) {
            report($exception);
            $database = ['status' => 'error', 'detail' => 'Database connection is unavailable.'];
        }

        $queueDriver = (string) config('queue.default');
        $queue = [
            'status' => $databaseAvailable ? ($queueDriver === 'sync' ? 'info' : 'ok') : 'error',
            'detail' => $databaseAvailable
                ? 'Queue driver: '.$queueDriver.'.'
                : 'Queue status is unavailable because the database is not responding.',
            'pending' => null,
            'failed' => null,
            'stale' => null,
            'overdue' => null,
        ];

        if ($databaseAvailable && $queueDriver === 'database') {
            $connection = config('queue.connections.database.connection') ?: config('database.default');
            $jobsTable = config('queue.connections.database.table', 'jobs');
            $failedTable = config('queue.failed.table', 'failed_jobs');
            $failedConnection = config('queue.failed.database') ?: config('database.default');

            try {
                $schema = Schema::connection($connection);
                if ($schema->hasTable($jobsTable)) {
                    $jobs = DB::connection($connection)->table($jobsTable);
                    $queue['pending'] = (clone $jobs)->count();
                    $queue['stale'] = (clone $jobs)
                        ->whereNotNull('reserved_at')
                        ->where('reserved_at', '<=', now()->subMinutes(SystemHealthThresholds::STALE_QUEUE_JOB_MINUTES)->timestamp)
                        ->count();
                    $queue['overdue'] = (clone $jobs)
                        ->whereNull('reserved_at')
                        ->where('available_at', '<=', now()->subMinutes(SystemHealthThresholds::STALE_QUEUE_JOB_MINUTES)->timestamp)
                        ->count();
                } else {
                    $queue['status'] = 'warning';
                    $queue['detail'] = 'Database queue is selected, but its jobs table is missing.';
                }

                $failedSchema = Schema::connection($failedConnection);
                if ($failedSchema->hasTable($failedTable)) {
                    $queue['failed'] = DB::connection($failedConnection)->table($failedTable)->count();
                }
            } catch (Throwable $exception) {
                report($exception);
                $queue['status'] = 'error';
                $queue['detail'] = 'Queue tables could not be checked.';
            }
        } elseif ($databaseAvailable && $queueDriver !== 'sync') {
            $queue['detail'] .= ' Pending counts are available from the queue backend, not this dashboard.';
        }

        $documents = ['pending' => null, 'processing' => null, 'failed' => null, 'stuck' => null];
        if ($databaseAvailable) {
            try {
                $counts = Document::query()
                    ->selectRaw("SUM(CASE WHEN processing_status = 'pending' THEN 1 ELSE 0 END) as pending")
                    ->selectRaw("SUM(CASE WHEN processing_status = 'processing' THEN 1 ELSE 0 END) as processing")
                    ->selectRaw("SUM(CASE WHEN processing_status = 'failed' THEN 1 ELSE 0 END) as failed")
                    ->first();
                $documents = [
                    'pending' => (int) ($counts->pending ?? 0),
                    'processing' => (int) ($counts->processing ?? 0),
                    'failed' => (int) ($counts->failed ?? 0),
                    'stuck' => Document::query()
                        ->where('processing_status', 'processing')
                        ->where('updated_at', '<=', now()->subMinutes(SystemHealthThresholds::STUCK_PROCESSING_MINUTES))
                        ->count(),
                ];
            } catch (Throwable $exception) {
                report($exception);
            }
        }

        $aiAvailable = app(AiProvider::class)->available();
        $mailDriver = (string) config('mail.default', 'unknown');
        $mailDeliveryConfigured = ! in_array($mailDriver, ['log', 'array'], true);
        $storageDriver = (string) config('filesystems.disks.private.driver', 'unknown');
        if ($storageDriver === 'local') {
            $storageRoot = (string) config('filesystems.disks.private.root', '');
            $storageReady = $storageRoot !== '' && is_dir($storageRoot) && is_writable($storageRoot);
            $storage = [
                'status' => $storageReady ? 'ok' : 'error',
                'driver' => $storageDriver,
                'detail' => $storageReady
                    ? 'Private storage directory exists and is writable.'
                    : 'Private storage directory is missing or not writable.',
            ];
        } else {
            $storage = [
                'status' => 'info',
                'driver' => $storageDriver,
                'detail' => 'Driver is configured; remote storage connectivity has not been tested.',
            ];
        }
        $mail = [
            'driver' => $mailDriver,
            'status' => $mailDeliveryConfigured ? 'info' : 'warning',
            'detail' => $mailDeliveryConfigured
                ? 'Driver is configured; email delivery has not been tested.'
                : 'This driver does not deliver email to recipients. Configure a delivery mailer for notifications.',
        ];
        $backup = app(LocalBackupService::class)->health();

        $healthIssues = [];
        if ($database['status'] === 'error') {
            $healthIssues[] = 'Database unavailable';
        }
        if ($queue['status'] === 'error') {
            $healthIssues[] = 'Queue check failed';
        } elseif ($queue['status'] === 'warning') {
            $healthIssues[] = 'Queue configuration needs attention';
        }
        if (($queue['failed'] ?? 0) > 0) {
            $healthIssues[] = 'Failed queue jobs';
        }
        if (($queue['stale'] ?? 0) > 0 || ($queue['overdue'] ?? 0) > 0) {
            $healthIssues[] = 'Stale or overdue queue jobs';
        }
        if (($documents['failed'] ?? 0) > 0) {
            $healthIssues[] = 'Failed document processing';
        }
        if (($documents['stuck'] ?? 0) > 0) {
            $healthIssues[] = 'Stuck document processing';
        }
        if ($databaseAvailable && $documents['pending'] === null) {
            $healthIssues[] = 'Document processing counts unavailable';
        }
        if ($mail['status'] === 'warning') {
            $healthIssues[] = 'Email driver does not deliver messages';
        }
        if ($storage['status'] === 'error') {
            $healthIssues[] = 'Private storage unavailable';
        }
        if ($backup['last_backup_at'] === null) {
            $healthIssues[] = 'Encrypted backup missing, stale, or unavailable';
        } elseif ($backup['stale'] === true) {
            $healthIssues[] = 'Encrypted backup is stale';
        }
        if (($backup['integrity']['status'] ?? 'skipped') === 'warning') {
            $healthIssues[] = 'Backup integrity not verified or failed';
        }
        if (($backup['offsite']['status'] ?? 'skipped') === 'warning') {
            $healthIssues[] = 'Off-site backup copy missing or stale';
        }
        $healthStatus = in_array($database['status'], ['error'], true)
            || in_array($queue['status'], ['error'], true)
            || $storage['status'] === 'error'
                ? 'error'
                : ($healthIssues === [] ? 'ok' : 'warning');

        return view('admin.system-health.index', [
            'database' => $database,
            'queue' => $queue,
            'documents' => $documents,
            'backup' => $backup,
            'health' => [
                'status' => $healthStatus,
                'issues' => $healthIssues,
            ],
            'stuckProcessingMinutes' => SystemHealthThresholds::STUCK_PROCESSING_MINUTES,
            'staleQueueJobMinutes' => SystemHealthThresholds::STALE_QUEUE_JOB_MINUTES,
            'backupStaleHours' => SystemHealthThresholds::BACKUP_STALE_HOURS,
            'ai' => [
                'status' => $aiAvailable ? 'ok' : 'info',
                'detail' => $aiAvailable
                    ? 'AI provider is available.'
                    : 'AI provider is not configured; rule-based processing remains available.',
            ],
            'mail' => $mail,
            'storage' => $storage,
        ]);
    }
}
