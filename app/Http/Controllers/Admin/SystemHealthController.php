<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Document;
use App\Services\Ai\AiProvider;
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
        ];

        if ($databaseAvailable && $queueDriver === 'database') {
            $connection = config('queue.connections.database.connection') ?: config('database.default');
            $jobsTable = config('queue.connections.database.table', 'jobs');
            $failedTable = config('queue.failed.table', 'failed_jobs');
            $failedConnection = config('queue.failed.database') ?: config('database.default');

            try {
                $schema = Schema::connection($connection);
                if ($schema->hasTable($jobsTable)) {
                    $queue['pending'] = DB::connection($connection)->table($jobsTable)->count();
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

        $documents = ['pending' => null, 'processing' => null, 'failed' => null];
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
                ];
            } catch (Throwable $exception) {
                report($exception);
            }
        }

        $aiAvailable = app(AiProvider::class)->available();

        return view('admin.system-health.index', [
            'database' => $database,
            'queue' => $queue,
            'documents' => $documents,
            'ai' => [
                'status' => $aiAvailable ? 'ok' : 'info',
                'detail' => $aiAvailable
                    ? 'AI provider is available.'
                    : 'AI provider is not configured; rule-based processing remains available.',
            ],
            'mailDriver' => config('mail.default'),
            'storageDriver' => config('filesystems.disks.private.driver', 'unknown'),
        ]);
    }
}
