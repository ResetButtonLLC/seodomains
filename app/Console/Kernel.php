<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use Carbon\Carbon;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        Commands\MiralinksCommand::class
    ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        $schedule->command('domains:sape')->weeklyOn(6, '0:10');
        $schedule->command('domains:miralinks')->weeklyOn(6, '6:00');
        $schedule->command('domains:rotapost')->weeklyOn(6, '12:00');
        $schedule->command('domains:gogetlinks')->weeklyOn(6, '18:00');
        $schedule->command('domains:collaborator')->weeklyOn(6, '23:00');

        $schedule->command('domains:ahrefs',[
            '--mode' => 'all',
            '--days' => '15'
        ])->weeklyOn(7, '6:00');
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
