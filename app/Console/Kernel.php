<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        Commands\ReconcilePayouts::class,
        Commands\ProcessPendingRefunds::class,
        Commands\TestRefund::class,
        Commands\TestRefundStatus::class,
        Commands\ReconcileTransfers::class,
        Commands\DebugPayinStatus::class,
        Commands\ProtectedAutoRelease::class,
    ];

    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        // Run the refund processor every 5 minutes
        $schedule->command('refunds:process-pending')
                 ->everyFiveMinutes()
                 ->withoutOverlapping()
                 ->appendOutputTo(storage_path('logs/refund-processor.log'));
            
        $schedule->command('texapay:reconcile-payouts --limit=50')
            ->everyMinute() // Changed to every minute for testing
            ->withoutOverlapping()
            ->runInBackground()
            ->appendOutputTo(storage_path('logs/reconcile-payouts.log'));

        // Reconcile transfers periodically (in addition to webhooks)
        $schedule->command('texapay:reconcile-transfers --limit=100')
            ->everyTwoMinutes()
            ->withoutOverlapping()
            ->runInBackground()
            ->appendOutputTo(storage_path('logs/reconcile-transfers.log'));

        // Optional: targeted payout status debug sweep (disabled by default)
        // $schedule->command('texapay:debug-payout --id=24 --apply')
        //     ->everyTenMinutes()
        //     ->runInBackground();
            
        // Log that the scheduler is running
        $schedule->call(function () {
            \Log::info('Scheduler is running at ' . now());
        })->everyMinute();

        // Protected auto-release: run every 10 minutes
        $schedule->command('protected:auto-release --limit=100')
            ->everyTenMinutes()
            ->withoutOverlapping()
            ->runInBackground()
            ->appendOutputTo(storage_path('logs/protected-auto-release.log'));

        // AML: Batch evaluate rules hourly over recent successful transfers (last 24h)
        $schedule->call(function () {
            try {
                $since = now()->subDay();
                $transfers = \App\Models\Transfer::query()
                    ->whereIn('status', ['completed','payout_success'])
                    ->where('updated_at', '>=', $since)
                    ->limit(1000)
                    ->get();
                $evaluator = app(\App\Services\AmlRuleEvaluator::class);
                foreach ($transfers as $t) {
                    $evaluator->evaluateTransfer($t, 'batch_hourly');
                }
                \Log::info('AML batch rule evaluation completed', ['count' => $transfers->count()]);
            } catch (\Throwable $e) {
                \Log::error('AML batch evaluation failed', ['error' => $e->getMessage()]);
            }
        })->hourly()->runInBackground();

        // Screening: Daily sanctions/adverse media rescan (lightweight stub)
        $schedule->call(function () {
            try {
                $screen = app(\App\Services\ScreeningService::class);
                \App\Models\User::query()
                    ->whereNull('deleted_at')
                    ->limit(200)
                    ->orderBy('id')
                    ->chunk(200, function ($users) use ($screen) {
                        foreach ($users as $u) {
                            $screen->runUserScreening($u, 'periodic');
                        }
                    });
                \Log::info('Screening daily rescan enqueued');
            } catch (\Throwable $e) {
                \Log::error('Screening daily rescan failed', ['error' => $e->getMessage()]);
            }
        })->dailyAt('02:00')->runInBackground();

        // Notifications: Daily pruning of old notification records (configurable retention)
        $schedule->call(function () {
            try {
                $days = (int) config('notifications.retention_days', 90);
                $cutoff = now()->subDays($days);
                \App\Models\UserNotification::where('created_at', '<', $cutoff)->chunkById(1000, function ($chunk) {
                    foreach ($chunk as $row) { $row->delete(); }
                });
                \App\Models\NotificationEvent::where('created_at', '<', $cutoff)->chunkById(1000, function ($chunk) {
                    foreach ($chunk as $row) { $row->delete(); }
                });
                \Log::info('Notifications pruning completed', ['retention_days' => $days]);
            } catch (\Throwable $e) {
                \Log::error('Notifications pruning failed', ['error' => $e->getMessage()]);
            }
        })->dailyAt('03:30')->runInBackground();

        // PEP: Weekly rescan (subset for demo)
        $schedule->call(function () {
            try {
                $screen = app(\App\Services\ScreeningService::class);
                \App\Models\User::query()
                    ->whereNull('deleted_at')
                    ->limit(200)
                    ->orderBy('id')
                    ->chunk(200, function ($users) use ($screen) {
                        foreach ($users as $u) {
                            $screen->runUserScreening($u, 'periodic');
                        }
                    });
                \Log::info('PEP weekly rescan enqueued');
            } catch (\Throwable $e) {
                \Log::error('PEP weekly rescan failed', ['error' => $e->getMessage()]);
            }
        })->weeklyOn(1, '03:00')->runInBackground();

        // EDD: Six-month re-verification for high-risk/PEP users (daily sweep)
        $schedule->call(function () {
            try {
                $enabled = (bool) \App\Models\AdminSetting::getValue('aml.edd.six_month_reverify_enabled', true);
                if (!$enabled) { return; }
                $since = now()->subMonths(6);
                $screen = app(\App\Services\ScreeningService::class);
                // Find users with EDD cases indicating high-risk/PEP whose case updated_at is older than 6 months
                \App\Models\EddCase::query()
                    ->whereIn('status', ['approved','closed','review'])
                    ->where('updated_at', '<=', $since)
                    ->where(function ($q) {
                        $q->where('metadata->senior_mgmt_required', true)
                          ->orWhere('metadata->requires_mlro_approval', true);
                    })
                    ->limit(200)
                    ->chunk(200, function ($cases) use ($screen) {
                        foreach ($cases as $case) {
                            if ($case->user) {
                                $screen->runUserScreening($case->user, 'six_month_reverify');
                            }
                        }
                    });
                \Log::info('EDD six-month reverify sweep completed');
            } catch (\Throwable $e) {
                \Log::error('EDD six-month reverify sweep failed', ['error' => $e->getMessage()]);
            }
        })->dailyAt('04:00')->runInBackground();

        // STR: Daily review reminder for open drafts
        $schedule->call(function () {
            try {
                $open = \App\Models\AmlStr::query()->where('status', 'draft')->count();
                if ($open > 0) {
                    \Log::warning('STR review pending', ['open_drafts' => $open]);
                }
            } catch (\Throwable $e) {
                \Log::error('STR review reminder failed', ['error' => $e->getMessage()]);
            }
        })->dailyAt('09:00')->runInBackground();
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');
        require base_path('routes/console.php');
    }
}
