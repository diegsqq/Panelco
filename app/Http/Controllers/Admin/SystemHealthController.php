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
        try {
            DB::select('SELECT 1');
            $database = ['status' => 'ok', 'detail' => 'Database connection is responding.'];
        } catch (Throwable $exception) {
            report($exception);
            $database = ['status' => 'error', 'detail' => 'Database connection is unavailable.'];
        }

        $queueDriver = (string) config('queue.default');
        $queue = [
            'status' => $queueDriver === 'sync' ? 'info' : 'ok',
            'detail' => 'Queue driver: '.$queueDriver.'.',
            'pending' => null,
            'failed' => null,
        ];

        if ($queueDriver === 'database') {
            $connection = config('queue.connections.database.connection') ?: config('database.default');
            $jobsTable = config('queue.connections.database.table', 'jobs');
            $failedTable = config('queue.failed.table', 'failed_jobs');

            try {
                $schema = Schema::connection($connection);
                if ($schema->hasTable($jobsTable)) {
                    $queue['pending'] = DB::connection($connection)->table($jobsTable)->count();
                } else {
                    $queue['status'] = 'warning';
                    $queue['detail'] = 'Database queue is selected, but its jobs table is missing.';
                }

                if ($schema->hasTable($failedTable)) {
                    $queue['failed'] = DB::connection($connection)->table($failedTable)->count();
                }
            } catch (Throwable $exception) {
                report($exception);
                $queue['status'] = 'error';
                $queue['detail'] = 'Queue tables could not be checked.';
            }
        } elseif ($queueDriver !== 'sync') {
            $queue['detail'] .= ' Pending counts are available from the queue backend, not this dashboard.';
        }

        $documents = Document::query()
            ->selectRaw("SUM(CASE WHEN processing_status = 'pending' THEN 1 ELSE 0 END) as pending")
            ->selectRaw("SUM(CASE WHEN processing_status = 'processing' THEN 1 ELSE 0 END) as processing")
            ->selectRaw("SUM(CASE WHEN processing_status = 'failed' THEN 1 ELSE 0 END) as failed")
            ->first();

        $aiAvailable = app(AiProvider::class)->available();

        return view('admin.system-health.index', [
            'database' => $database,
            'queue' => $queue,
            'documents' => [
                'pending' => (int) ($documents->pending ?? 0),
                'processing' => (int) ($documents->processing ?? 0),
                'failed' => (int) ($documents->failed ?? 0),
            ],
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
