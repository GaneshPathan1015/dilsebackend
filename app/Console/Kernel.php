<?php

// namespace App\Console;

// use Illuminate\Console\Scheduling\Schedule;
// use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

// class Kernel extends ConsoleKernel
// {
//     /**
//      * Define the application's command schedule.
//      */
//     protected function schedule(Schedule $schedule): void
//     {
//         // $schedule->command('inspire')->hourly();
//         $schedule->command('import:diamond-data')->dailyAt('08:00');
//         $schedule->command('products:update-prices-from-metal')
//              ->dailyAt('09:00')
//              ->timezone('Asia/Kolkata');
//     } 

//     /**
//      * Register the commands for the application.
//      */
//     protected function commands(): void
//     {
//         $this->load(__DIR__.'/Commands');

//         require base_path('routes/console.php');
//     }
// }


namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    protected function schedule(Schedule $schedule): void
    {
        /**
         * 🔹 Auto Diamond Import (EVERY 10 MIN)
         */
        $schedule->command('import:diamond-data-v2')
            ->everyTenMinutes()
            ->withoutOverlapping()
            ->runInBackground()
            ->appendOutputTo(storage_path('logs/diamond-import.log'));

        /**
         * 🔹 Metal price update (existing)
         */
        $schedule->command('products:update-prices-from-metal')
            ->dailyAt('09:00')
            ->timezone('Asia/Kolkata')
            ->withoutOverlapping()
            ->appendOutputTo(storage_path('logs/metal-price.log'));
    }

    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');
        require base_path('routes/console.php');
    }
}
