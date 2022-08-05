<?php

namespace App\Console\Commands;

use App\Models\Domain;
use App\Services\DomainExporter;
use Illuminate\Console\Command;

class GenerateXLSCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'domains:export';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Export domains to XLS file';

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
        $domains = Domain::getDomainsForExport();
        DomainExporter::exportXLS($domains);

        return 0;
    }

}
