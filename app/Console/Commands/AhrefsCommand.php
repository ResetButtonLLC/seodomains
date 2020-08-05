<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Domains;
use App\Helpers\ApiPromodoHelper;
use Carbon\Carbon;

class AhrefsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'domains:ahrefs
        {--mode=fill : launch modes => [1] "fill" - fill only empty data [2] "all" - refresh all domains } 
        {--days=0 : [only for "all" launch mode] refresh only data older than this value in days}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'get Metrics: Ahrefs DR, Ahrefs InLinks';

    /**
     * Create a new command instance.
     *
     * @return void
     */

    private $api;

    public function __construct()
    {
        parent::__construct();

        $this->api = new ApiPromodoHelper();
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
            $domains_urls = Domains::whereNull('ahrefs_dr')->orWhereNull('ahrefs_inlinks')->get('url');
            $this->line('MODE [fill] : updating domains with empty data');
        } else {
            $this->line('MODE [refresh] : refreshing data for all domains');
            //Option days
            $days = $this->option('days');
            if (intval($days>0)) {
                $this->line("OPTION: DAYS. Quering domains with data older than $days days");
                $domains_urls = Domains::whereDate('ahrefs_updated_at','<=',Carbon::now()->subDays($days))->orwhereNull('ahrefs_updated_at')->get('url');
            } else {
                $domains_urls = Domains::all('url');
            }
        }

        foreach ($domains_urls as $domain) {
            $domains[] = $domain->url;
        }
        $domains = array_filter($domains);
        $this->line('Get '.count($domains).' domains from database');

        $this->line('Fetching Ahrefs DR / Inlinks / Traffic Top10 / Position Top10 ... ');

        $counter = array(
            'current' => 1,
            'total' => count($domains),
        );

        $api = new ApiPromodoHelper();

        foreach ($domains as $domain) {

            //Ahrefs DR
            $result = $api->makeRequest('ahrefs/public/getDomainRating',[$domain]);
            //todo
            /*
            array:1 [
                "error" => "Rate limit"
            ]
            */

            $ahrefs_data[$domain]['dr'] = isset(current($result)['domain_rating']) ? current($result)['domain_rating'] : null;

            //Ahrefs Inlinks
            $result = $api->makeRequest('ahrefs/public/getDomainLinks',[$domain]);
            $ahrefs_data[$domain]['inlinks'] = isset(current($result)["metrics"]["refdomains"]) ?  current($result)["metrics"]["refdomains"] : null;

            //Ahrefs positions & traffic
            $result = $api->makeRequest('ahrefs/public/positions_metrics',[$domain]);
            $ahrefs_data[$domain]['positions_top3'] = isset(current($result)["metrics"]["positions_top3"]) ? current($result)["metrics"]["positions_top3"] : null;
            $ahrefs_data[$domain]['positions_top10'] = isset(current($result)["metrics"]["positions_top10"]) ? current($result)["metrics"]["positions_top10"] : null;
            $ahrefs_data[$domain]['positions_top100'] = isset(current($result)["metrics"]["positions"]) ? current($result)["metrics"]["positions"] : null;
            $ahrefs_data[$domain]['traffic_top3'] = isset(current($result)["metrics"]["traffic_top3"]) ? (int)round(current($result)["metrics"]["traffic_top3"]) : null;
            $ahrefs_data[$domain]['traffic_top10'] = isset(current($result)["metrics"]["traffic_top10"]) ? (int)round(current($result)["metrics"]["traffic_top10"]) : null;
            $ahrefs_data[$domain]['traffic_top100'] = isset(current($result)["metrics"]["traffic"]) ? (int)round(current($result)["metrics"]["traffic"]) : null;

            $this->line('Ahrefs | '. $counter['current'].'/'.$counter['total'].' | Domain: '.$domain.'| DR:'.$ahrefs_data[$domain]['dr'].' Inlinks:'.$ahrefs_data[$domain]['inlinks'].' PositionsTop100:'.$ahrefs_data[$domain]['positions_top100'].' Traffic:'.$ahrefs_data[$domain]['traffic_top100']);

            //Import into DB
            Domains::where('url',$domain)->update([
                'ahrefs_dr' =>$ahrefs_data[$domain]['dr'],
                'ahrefs_inlinks' => $ahrefs_data[$domain]['inlinks'],
                'ahrefs_positions_top3' => $ahrefs_data[$domain]['positions_top3'],
                'ahrefs_positions_top10' => $ahrefs_data[$domain]['positions_top10'],
                'ahrefs_positions_top100' => $ahrefs_data[$domain]['positions_top100'],
                'ahrefs_traffic_top3' => $ahrefs_data[$domain]['traffic_top3'],
                'ahrefs_traffic_top10' => $ahrefs_data[$domain]['traffic_top10'],
                'ahrefs_traffic_top100' => $ahrefs_data[$domain]['traffic_top100'],
                'ahrefs_updated_at' => Carbon::now()]);

            $counter['current']++;

        }

        $this->info("Process complete");

    }

}
