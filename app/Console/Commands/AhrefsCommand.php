<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Domain;
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
            $domains_urls = Domain::whereNull('ahrefs_dr')->orWhereNull('ahrefs_inlinks')->get('url');
            $this->line('MODE [fill] : updating domains with empty data');
        } else {
            $this->line('MODE [all] : refreshing data for all domains');
            //Option days
            $days = $this->option('days');
            if (intval($days>0)) {
                $this->line("OPTION: DAYS. Quering domains with data older than $days days");
                $domains_urls = Domain::whereDate('ahrefs_updated_at','<=',Carbon::now()->subDays($days))->orwhereNull('ahrefs_updated_at')->get('url');
            } else {
                $domains_urls = Domain::all('url');
            }
        }

        foreach ($domains_urls as $domain) {
            $domains[] = $domain->url;
        }
        $domains = array_filter($domains);
        $this->line('Get '.count($domains).' domains from database');

        $this->line('Fetching Ahrefs DR / Ahrefs Traffic');

        $counter = array(
            'current' => 1,
            'total' => count($domains),
        );

        $api = new ApiPromodoHelper();

        foreach ($domains as $domain) {

            //Ahrefs DR
            $result = $api->makeRequest('v2/ahrefs/get?from=domain_rating&limit=1&target='.$domain.'&mode=subdomains');

            $domain_data = head(data_get($result,'data',[]));
            $ahrefs_data[$domain]['dr'] = data_get($domain_data,'domain.domain_rating',null);
            //Ahrefs Inlinks

            //Ahrefs positions & traffic
            $result = $api->makeRequest('v2/ahrefs/get?from=positions_metrics&limit=1&target='.$domain.'&mode=subdomains');
            $domain_data = head(data_get($result,'data',[]));
            $ahrefs_data[$domain]['positions_top3'] = data_get($domain_data,'metrics.positions_top3',null);
            $ahrefs_data[$domain]['positions_top10'] = data_get($domain_data,'metrics.positions_top10',null);
            $ahrefs_data[$domain]['positions_top100'] = data_get($domain_data,'metrics.positions',null);
            $ahrefs_data[$domain]['traffic_top3'] = data_get($domain_data,'metrics.traffic_top3',null);
            $ahrefs_data[$domain]['traffic_top10'] = data_get($domain_data,'metrics.traffic_top10',null);
            $ahrefs_data[$domain]['traffic_top100'] = data_get($domain_data,'metrics.traffic',null);

            $this->line('Ahrefs | '. $counter['current'].'/'.$counter['total'].' | Domain: '.$domain.'| DR:'.$ahrefs_data[$domain]['dr'].' PositionsTop100:'.$ahrefs_data[$domain]['positions_top100'].' Traffic:'.$ahrefs_data[$domain]['traffic_top100']);

            //Если где то в массиве есть null значит это ошибка запроса и в БД это писать не надо
            if (in_array(null,$ahrefs_data[$domain],true)) {
                $this->error('Ошибка получения данных от API для домена '.$domain);
            } else {
                //Import into DB
                Domain::where('url',$domain)->update([
                    'ahrefs_dr' =>$ahrefs_data[$domain]['dr'],
                    'ahrefs_positions_top3' => $ahrefs_data[$domain]['positions_top3'],
                    'ahrefs_positions_top10' => $ahrefs_data[$domain]['positions_top10'],
                    'ahrefs_positions_top100' => $ahrefs_data[$domain]['positions_top100'],
                    'ahrefs_traffic_top3' => (int)$ahrefs_data[$domain]['traffic_top3'],
                    'ahrefs_traffic_top10' => (int)$ahrefs_data[$domain]['traffic_top10'],
                    'ahrefs_traffic_top100' => (int)$ahrefs_data[$domain]['traffic_top100'],
                    'ahrefs_updated_at' => Carbon::now()]);
            }

            $counter['current']++;

        }

        $this->info("Process complete");

    }

}
