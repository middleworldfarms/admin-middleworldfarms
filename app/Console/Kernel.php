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
    protected $commands = [
        Commands\ManageSubscription::class,
        Commands\RunScheduledBackups::class,
        Commands\SyncFarmOSVarieties::class,
    ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        // Run scheduled backups hourly
        $schedule->command('backup:scheduled')
                 ->hourly()
                 ->withoutOverlapping()
                 ->runInBackground();

        // Sync plant varieties from FarmOS daily
        $schedule->command('farmos:sync-varieties')
                 ->daily()
                 ->at('02:00')
                 ->withoutOverlapping()
                 ->runInBackground();
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
