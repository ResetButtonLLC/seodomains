<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Domains;
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

        $domains_table = (new Domains)->getTable();
        $domain_skip = $this->option('skip');

        /* $domain_mode = $this->option('mode');

        if ($domain_mode == 'update') {
            $this->info('Updating domains with empty data');
            $domains_urls = DB::table($domains_table)->select('url')->where('majestic_tf','<','1')->skip($domain_skip)->take(PHP_INT_MAX)->get();
        } else {
            $domains_urls = DB::table($domains_table)->select('url')->skip($domain_skip)->take(PHP_INT_MAX)->get();
        } */

        $domains_urls = DB::table($domains_table)->select('url')->skip($domain_skip)->take(PHP_INT_MAX)->get();
        foreach ($domains_urls as $domain) {
            $domains[] = $domain->url;
        }
        $domains = array_filter($domains);

        if ($domain_skip) {
            $this->info('Skipping ' . $domain_skip . ' domains');
        }
        $this->info(count($domains).' domains loaded from database');

        $domain_limit = $this->option('limit');
        if ($domain_limit>0) {
            $domains = array_slice($domains,0,$domain_limit);
            $this->info('OPTION:LIMIT. Run only '.count($domains).' domains');
        }

        $api = new ApiPromodoHelper();

        $this->info('Asking Majestic for CF/TF and updating DB');
        $bar = $this->output->createProgressBar(count($domains));
        $bar->start();

        foreach ($domains as $domain) {
            $result = $api->makeRequest('majestic/scrape',[$domain]);

            if((isset(current($result)['tf'])) && (isset(current($result)['cf']))) {
                $majestic_data[$domain]['majestic_tf'] = current($result)['tf'];
                $majestic_data[$domain]['majestic_cf'] = current($result)['cf'];
            } else {
                $majestic_data[$domain]['majestic_tf'] = -1;
                $majestic_data[$domain]['majestic_cf'] = -1;
                print_r($domain);
            }

            DB::table($domains_table)->where('url',$domain)->update(['majestic_tf' => $majestic_data[$domain]['majestic_tf'],'majestic_cf' => $majestic_data[$domain]['majestic_tf']]);

            sleep ( mt_rand(60,90));

            $bar->advance();

        }
        $bar->finish();

        $this->info("Process complete");

    }

}
