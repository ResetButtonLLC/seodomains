<?php

namespace App\Console;

use App\Models\Domain;
use App\Services\DomainExporter;
use App\Services\DomainProcessor;
use App\Services\Parsers\Collaborator;
use App\Services\Parsers\Prnews;
use App\Services\Parsers\Prposting;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

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

        $schedule->call(function () {
            try {
                (new Collaborator())->parse();
            } catch (\Exception $exception) {
                Log::error('Collaborator Update Failed');
            }

            try {
                (new Prnews())->parse();
            } catch (\Exception $exception) {
                Log::error('Prnews Update Failed');
            }

            try {
                (new Prposting())->parse();
            } catch (\Exception $exception) {
                Log::error('Prposting Update Failed');
            }

            try {
                DomainProcessor::process();
            } catch (\Exception $exception) {
                Log::error('Domains post processing Failed');
            }

            try {
                $domains = Domain::getDomainsForExport();
                DomainExporter::exportXLS($domains);
            } catch (\Exception $exception) {
                Log::error('Exposting to XLS Failed');
            }

        })->weeklyOn(5, '22:00');

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
