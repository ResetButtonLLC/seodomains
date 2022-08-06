<?php

namespace App\Console\Commands;

use App\Models\Domain;
use App\Services\DomainExporter;
use App\Services\DomainProcessor;
use App\Services\Parsers\Collaborator;
use App\Services\Parsers\Prnews;
use App\Services\Parsers\Prposting;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class BatchCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'domains:all';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Process All operations';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {

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

        return 0;
    }
}
