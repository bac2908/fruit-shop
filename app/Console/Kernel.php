<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        $businessTimezone = (string) config('app.display_timezone', 'Asia/Ho_Chi_Minh');

        $schedule->command('shop:cancel-expired-bank-transfers')->hourly()->withoutOverlapping();
        $schedule->command('shop:cancel-expired-momo-orders')->everyFiveMinutes()->withoutOverlapping();
        $schedule->command('shop:alert-low-stock')
            ->dailyAt('08:00')
            ->timezone($businessTimezone)
            ->withoutOverlapping();
        $schedule->command('shop:prune-security-data')
            ->dailyAt('02:30')
            ->timezone($businessTimezone)
            ->withoutOverlapping();
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
