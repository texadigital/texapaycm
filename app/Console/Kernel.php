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
            
        // Log that the scheduler is running
        $schedule->call(function () {
            \Log::info('Scheduler is running at ' . now());
        })->everyMinute();
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
