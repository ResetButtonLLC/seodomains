<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Domains;
use Illuminate\Support\Facades\DB;
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
        {--limit=0: Run only X domains}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'get Ahrefs DR and In/Out Links for domains';

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
            $this->info('MODE [fill] : updating domains with empty data');
        } else {
            $domains_urls = Domains::all('url');
            $this->info('MODE [refresh] : updating data for all domains');
        }

        foreach ($domains_urls as $domain) {
            $domains[] = $domain->url;
        }
        $domains = array_filter($domains);
        $this->info('Get '.count($domains).' domains from database');

        //Option limit
        $domain_limit = $this->option('limit');
        if ($domain_limit>0) {
            $domains = array_slice($domains,0,$domain_limit);
            $this->info('OPTION:LIMIT. Run only '.count($domains).' domains');
        }

        $api = new ApiPromodoHelper();

        $this->info('Fetching Ahrefs DR & Inlinks ... ');
        $bar = $this->output->createProgressBar(count($domains));
        $bar->start();

        foreach ($domains as $domain) {
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

            $bar->advance();

        }
        $bar->finish();
        $this->info("\n");

        $this->info("Process complete");

    }

}
