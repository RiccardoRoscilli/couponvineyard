<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        $schedule->command('coupons:update-expiration-dates')->dailyAt('02:00');
       // $schedule->command('bookings:sync')->everyThirtyMinutes()->withoutOverlapping();
        
        // Slope API sync - runs every hour
        $schedule->command('slope:sync')
            ->hourly()
            ->withoutOverlapping()
            ->onOneServer();
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
