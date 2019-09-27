<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Domains;
use Illuminate\Support\Facades\DB;
use App\Helpers\ApiPromodoHelper;

class SerpstatCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'domains:traffic {--limit=0: Run only X domains}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'get site traffic form Serpstat, source - google.ua';

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
        $domains_urls = DB::table($domains_table)->select('url')->get();

        foreach ($domains_urls as $domain) {
            $domains[] = $domain->url;
        }
        $domains = array_filter($domains);
        $this->info('Get '.count($domains).' domains from database');

        $domain_limit = $this->option('limit');
        if ($domain_limit>0) {
            $domains = array_slice($domains,0,$domain_limit);
            $this->info('OPTION:LIMIT. Run only '.count($domains).' domains');
        }

        $api = new ApiPromodoHelper();
        //Serpstat google.ua traffic

        $this->info('Asking Serpstat for traffic [Google.ua]');
        $bar = $this->output->createProgressBar(count($domains));
        $bar->start();

        foreach ($domains as $domain) {
            $result = $api->makeOneRequest('serpstat/scrapeone',$domain);
            if(isset($result['traff'])) {
                $serpstat_data[$domain]['serpstat_traffic'] = $result['traff'];
            } else {
                $serpstat_data[$domain]['serpstat_traffic'] = -1;
            }
            $bar->advance();

        }
        $bar->finish();
        $this->info("\n");

        //Import into DB
        $this->info("Updating DB");

        $bar = $this->output->createProgressBar(count($serpstat_data));
        $bar->start();

       foreach ($serpstat_data as $serpstat_domain_name => $serpstat_domain_data ) {
            DB::table($domains_table)->where('url',$serpstat_domain_name)->update(['serpstat_traffic' => $serpstat_domain_data['serpstat_traffic']]);
            $bar->advance();
        }
        $bar->finish();
        $this->info("\n");

        $this->info("Process complete");

    }

}
