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
        {--days=30: [only for "all" launch mode] refresh only data older than this value in days}';

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


        $api = new ApiPromodoHelper();

        $this->line('Fetching Ahrefs DR & Inlinks ... ');

        $counter = array(
            'current' => 0,
            'total' => count($domains),
        );

        foreach ($domains as $domain) {

            $this->line(++$counter['current'].'/'.$counter['total'].' | Domain: '.$domain);

            //Ahrefs DR
            $result = $api->makeRequest('ahrefs/public/getDomainRating',[$domain]);
            if(isset(current($result)['domain_rating'])) {
                $ahrefs_data[$domain]['dr'] = current($result)['domain_rating'];
            } else {
                $ahrefs_data[$domain]['dr'] = null;
            }

            //Ahrefs Inlinks
            $result = $api->makeRequest('ahrefs/public/getDomainLinks',[$domain]);
            if(isset(current($result)["metrics"]["refdomains"])) {
                $ahrefs_data[$domain]['inlinks'] = current($result)["metrics"]["refdomains"];
            } else {
                $ahrefs_data[$domain]['inlinks'] = null;
            }

            //Import into DB
            Domains::where('url',$domain)->update(['ahrefs_dr' =>$ahrefs_data[$domain]['dr'],'ahrefs_inlinks' => $ahrefs_data[$domain]['inlinks'], 'ahrefs_updated_at' => Carbon::now()]);

        }

        $this->info("Process complete");

    }

}
