<?php

namespace App\Console\Commands;

use App\Services\DomainProcessor;
use Illuminate\Console\Command;

class ProcessDomains extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'domains:process';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Process data from parser tables in main table';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        DomainProcessor::process();
        return 0;
    }
}
