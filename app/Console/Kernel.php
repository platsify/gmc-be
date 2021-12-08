<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use App\Console\Commands\test_map_product_to_project;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        //
    ];

    /**
     * Define the application's command schedule.
     *
     * @param \Illuminate\Console\Scheduling\Schedule $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
		$schedule->command('push_gmc')->everyFiveMinutes();
		//$schedule->command('test_map 61a0f61c06a98a7057665fe5')->everyFiveMinutes();
		//$schedule->command('test_map 61a0f64d60aa7173c30abb14')->everyFiveMinutes();
		//$schedule->command('test_map 61a0f67c06a98a7057665fe6')->everyFiveMinutes();

        // $schedule->command('inspire')->hourly();
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__ . '/Commands');

        require base_path('routes/console.php');
    }
}
