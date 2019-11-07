<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Domains;
use App\Helpers\ApiPromodoHelper;
use Carbon\Carbon;

class SerpstatCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'domains:traffic 
        {--mode=fill : [1] refresh - refresh all data [2] fill - get data only for domains with no data}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Get site traffic. Source - Serpstat, google.com.ua';

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

        //Option: mode
        $mode = $this->option('mode');

        if ($mode == 'fill') {
            $domains_urls = Domains::whereNull('serpstat_traffic')->get('url');
            $this->info('MODE [fill] : updating domains with empty data');
        } else {
            $domains_urls = Domains::all('url');
            $this->info('MODE [refresh] : updating data for all domains');
        }

        foreach ($domains_urls as $domain) {
            $domains[] = $domain->url;
        }
        $domains = array_filter($domains);

        $this->info(count($domains).' domains loaded from database');

        $api = new ApiPromodoHelper();
        //Serpstat google.ua traffic

        $this->info('Asking Serpstat for traffic [Google.ua]');
        $bar = $this->output->createProgressBar(count($domains));
        $bar->start();

        foreach ($domains as $domain) {
            //getresult
            $result = $api->makeOneRequest('serpstat/scrapeone',$domain);
            if(isset($result['traff'])) {
                $serpstat_data[$domain]['serpstat_traffic'] = $result['traff'];
            } else {
                $serpstat_data[$domain]['serpstat_traffic'] = null;
            }

            //Import into DB
            Domains::where('url',$domain)->update(['serpstat_traffic' =>$serpstat_data[$domain]['serpstat_traffic'], 'traffic_updated_at' => Carbon::now()]);
            $bar->advance();

        }

        $bar->finish();
        $this->info("\n");

        $this->info("Process complete");

    }

}
