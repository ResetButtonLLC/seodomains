<?php

namespace App\Console\Commands;

use App\Models\{
    Domains,
    Prnews
};
use Illuminate\Support\Facades\Artisan;
use Symfony\Component\DomCrawler\Crawler;
use App\Helpers\DomainsHelper;

class PrnewsCommand extends ParserCommand {

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'domains:prnews';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Get domains from prnews';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct() {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle() {
        $this->initLog('prnews');

        $this->writeLog('prnews get with dusk test');

        Artisan::call('dusk', [
            ' --filter' => 'PrnewsTest',
        ]);

        $this->call('domains:finalize', [
            '--table' => 'prnews',
        ]);

        return true;
    }
}