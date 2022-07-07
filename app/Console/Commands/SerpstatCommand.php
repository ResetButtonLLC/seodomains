<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Domain;
use App\Helpers\ApiPromodoHelper;
use Carbon\Carbon;

class SerpstatCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'domains:serpstat 
        {--mode=fill : launch modes => [1] "fill" - fill only empty data [2] "all" - refresh all domains } 
        {--days=30: [only for "all" launch mode] refresh only data older than this value in days}';

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
            $domains_urls = Domain::whereNull('serpstat_traffic')->get('url');
            $this->line('MODE [fill] : updating domains with empty data');
        } else {
            $this->line('MODE [refresh] : refreshing data for all domains');
            //Option days
            $days = $this->option('days');
            if (intval($days>0)) {
                $this->line("OPTION: DAYS. Quering domains with data older than $days days");
                $domains_urls = Domain::whereDate('traffic_updated_at','<=',Carbon::now()->subDays($days))->orwhereNull('serpstat_traffic')->get('url');
            } else {
                $domains_urls = Domain::all('url');
            }
        }

        foreach ($domains_urls as $domain) {
            $domains[] = $domain->url;
        }
        $domains = array_filter($domains);

        $this->line(count($domains).' domains loaded from database');

        $api = new ApiPromodoHelper();
        //Serpstat google.ua traffic

        $this->line('Asking Serpstat for traffic [Google.ua]');

        $counter = array(
            'current' => 0,
            'total' => count($domains),
        );

        foreach ($domains as $domain) {

            $this->line(++$counter['current'].'/'.$counter['total'].' | Domain: '.$domain);

            //getresult
            $result = $api->makeOneRequest('serpstat/scrapeone',$domain);

            if((isset(current($result)['traff']))) {
                $serpstat_data[$domain]['serpstat_traffic'] = current($result)['traff'];
            } else {
                $serpstat_data[$domain]['serpstat_traffic'] = null;
            }

            //Import into DB
            Domain::where('url',$domain)->update(['serpstat_traffic' =>$serpstat_data[$domain]['serpstat_traffic'], 'traffic_updated_at' => Carbon::now()]);

        }

        $this->line("Process complete");

    }

}
