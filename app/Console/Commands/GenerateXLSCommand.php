<?php

namespace App\Console\Commands;

use App\Models\Domains;
use App\Services\DomainsService;
use Illuminate\Console\Command;

class GenerateXLSCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'domains:generate';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create new XLS file';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        ini_set('max_execution_time', 0);
        ini_set('memory_limit', '2048M');

        $domains = Domains::getDomainsForExport();

        return DomainsService::exportXLS(NULL, $domains);
    }

}
