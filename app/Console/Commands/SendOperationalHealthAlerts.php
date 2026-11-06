<?php

namespace App\Console\Commands;

use App\Mail\OperationalHealthAlert;
use App\Models\Document;
use App\Models\User;
use App\Support\SystemHealthThresholds;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Throwable;

class SendOperationalHealthAlerts extends Command
{
    protected $signature = 'smartdocs:send-health-alerts';

    protected $description = 'Email active administrators a rate-limited summary of processing issues';

    public function handle(): int
    {
        $cacheDriver = config('cache.stores.'.config('cache.default').'.driver', config('cache.default'));
        if (in_array($cacheDriver, ['array', 'null', 'octane'], true)) {
            $this->error('Operational alerts require a persistent cache store for reliable rate limiting.');

            return self::FAILURE;
        }

        $lock = Cache::lock('smartdocs:operational-health-alerts:lock', 120);
        if (! $lock->get()) {
            $this->info('Another operational alert check is already running.');

            return self::SUCCESS;
        }

        try {
            if (Cache::has('smartdocs:operational-health-alerts:last-sent')) {
                $this->info('Operational alerts are rate-limited; no email was sent.');

                return self::SUCCESS;
            }

            $issues = $this->findIssues();
            if ($issues === []) {
                $this->info('No operational issues detected.');

                return self::SUCCESS;
            }

            if (in_array((string) config('mail.default'), ['log', 'array'], true)) {
                $this->error('Operational alerts cannot be delivered: configure a delivery mailer instead of the log or array driver.');

                return self::FAILURE;
            }

            $admins = User::query()
                ->where('role', User::ROLE_ADMIN)
                ->where('is_active', true)
                ->whereNotNull('email')
                ->get();
            if ($admins->isEmpty()) {
                $this->error('Operational issues were found, but there are no active administrators to notify.');

                return self::FAILURE;
            }

            $sent = 0;
            $failed = 0;
            foreach ($admins as $admin) {
                try {
                    Mail::to($admin->email)->send(new OperationalHealthAlert($issues));
                    $sent++;
                } catch (Throwable $exception) {
                    report($exception);
                    $this->error("Could not send an operational alert to administrator #{$admin->id}.");
                    $failed++;
                }
            }

            if ($sent > 0) {
                Cache::put(
                    'smartdocs:operational-health-alerts:last-sent',
                    now()->toIso8601String(),
                    now()->addMinutes(SystemHealthThresholds::ALERT_COOLDOWN_MINUTES)
                );
            }

            if ($failed > 0) {
                return self::FAILURE;
            }

            $this->info("Operational alert sent to {$sent} active administrator(s).");

            return self::SUCCESS;
        } finally {
            $lock->release();
        }
    }

    /**
     * @return array<string, int>
     */
    private function findIssues(): array
    {
        $issues = [];
        $failedDocuments = Document::query()->where('processing_status', 'failed')->count();
        $stuckDocuments = Document::query()
            ->where('processing_status', 'processing')
            ->where('updated_at', '<=', now()->subMinutes(SystemHealthThresholds::STUCK_PROCESSING_MINUTES))
            ->count();

        if ($failedDocuments > 0) {
            $issues['Failed documents'] = $failedDocuments;
        }
        if ($stuckDocuments > 0) {
            $issues['Documents stuck in processing for over '.SystemHealthThresholds::STUCK_PROCESSING_MINUTES.' minutes'] = $stuckDocuments;
        }

        if (config('queue.default') === 'database') {
            $connection = config('queue.connections.database.connection') ?: config('database.default');
            $jobsTable = config('queue.connections.database.table', 'jobs');
            if (Schema::connection($connection)->hasTable($jobsTable)) {
                $jobs = DB::connection($connection)->table($jobsTable);
                $cutoff = now()->subMinutes(SystemHealthThresholds::STALE_QUEUE_JOB_MINUTES)->timestamp;
                $staleJobs = (clone $jobs)
                    ->whereNotNull('reserved_at')
                    ->where('reserved_at', '<=', $cutoff)
                    ->count();
                $overdueJobs = (clone $jobs)
                    ->whereNull('reserved_at')
                    ->where('available_at', '<=', $cutoff)
                    ->count();

                if ($staleJobs > 0) {
                    $issues['Queue jobs reserved for over '.SystemHealthThresholds::STALE_QUEUE_JOB_MINUTES.' minutes'] = $staleJobs;
                }
                if ($overdueJobs > 0) {
                    $issues['Queue jobs overdue for over '.SystemHealthThresholds::STALE_QUEUE_JOB_MINUTES.' minutes'] = $overdueJobs;
                }
            }

            $failedTable = config('queue.failed.table', 'failed_jobs');
            $failedConnection = config('queue.failed.database') ?: config('database.default');
            if (Schema::connection($failedConnection)->hasTable($failedTable)) {
                $failedJobs = DB::connection($failedConnection)->table($failedTable)->count();
                if ($failedJobs > 0) {
                    $issues['Failed queue jobs'] = $failedJobs;
                }
            }
        }

        return $issues;
    }
}
