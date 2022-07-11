<?php

namespace App\Console\Commands\Metrics;

use Illuminate\Console\Command;
use App\Models\Domain;
use Illuminate\Support\Facades\DB;
use App\Helpers\ApiPromodoHelper;

class MajesticCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'domains:majestic 
        {--limit=0 : Run only X domains}
        {--skip=0 : Skip X domains}
        {--mode=refresh : MODES: 1) refresh - refresh all data 2) update - get data only for domains with no data}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'get Majestic CF/TF';

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

        $domains_urls = Domain::whereNull('majestic_tf')->orWhereNull('majestic_tf')->get('url');
        foreach ($domains_urls as $domain) {
            $domains[] = $domain->url;
        }
        $domains = array_filter($domains);


        $this->info(count($domains).' domains loaded from database');

        if ($this->confirm('Update will cost ~'.round(count($domains)/1000,2).' $. Do you wish to continue?')) {

            $api = new ApiPromodoHelper();

            $this->info('Asking Majestic for CF/TF and updating DB');
            $bar = $this->output->createProgressBar(count($domains));
            $bar->start();

            foreach ($domains as $domain) {
                $result = $api->makeRequest('majestic/scrapeViaDomDetailer', [$domain]);

                if ((isset(current($result)['tf'])) && (isset(current($result)['cf']))) {
                    $majestic_data[$domain]['majestic_tf'] = current($result)['tf'];
                    $majestic_data[$domain]['majestic_cf'] = current($result)['cf'];
                } else {
                    $majestic_data[$domain]['majestic_tf'] = null;
                    $majestic_data[$domain]['majestic_cf'] = null;
                }

                Domain::where('url', $domain)->update(['majestic_tf' => $majestic_data[$domain]['majestic_tf'], 'majestic_cf' => $majestic_data[$domain]['majestic_tf']]);

                $bar->advance();

            }
            $bar->finish();

            $this->info("Process complete");
        } else {
            $this->line("Operation cancelled");
        }
    }

}
