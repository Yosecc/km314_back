<?php

namespace App\Console;

use App\Console\Commands\RecoverMessagesEmails;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    protected $commands = [
        RecoverMessagesEmails::class,
        \App\Console\Commands\ImportAccountingCsv::class,
        \App\Console\Commands\ProcessPackageReceptionAlerts::class,
        \App\Console\Commands\ProcessInterestedFollowUpReminders::class,
    ];

    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        // $schedule->command('inspire')->hourly();
        $schedule->command('app:recover-messages-emails')->everyThirtyMinutes();
        $schedule->command('package-receptions:process-alerts')->everyFiveMinutes()->withoutOverlapping();
        $schedule->command('interesteds:process-reminders')->everyFiveMinutes()->withoutOverlapping();
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
