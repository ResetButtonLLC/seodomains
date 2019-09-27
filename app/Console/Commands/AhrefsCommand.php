<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Domains;
use Illuminate\Support\Facades\DB;
use App\Helpers\ApiPromodoHelper;

class AhrefsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'domains:ahrefs';

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

        $domains_table = (new Domains)->getTable();
        $domains_urls = DB::table($domains_table)->select('url')->get();

        foreach ($domains_urls as $domain) {
            $domains[] = $domain->url;
        }

        //$domains = array_slice($domains,0,5); //DEBUG

        $this->info('Get '.count($domains).' domains from database');

        $ahrefs = new ApiPromodoHelper();
        //Ahrefs DR

        $this->info('Asking Ahrefs for DR');
        $bar = $this->output->createProgressBar(count($domains));
        $bar->start();
        $bar->setMessage('Asking Ahrefs for DR ... ');

        foreach ($domains as $domain) {
            $result = $ahrefs->makeRequest('getDomainRating',[$domain]);
            if($result) {
                $ahrefs_data[$domain]['dr'] = current($result)['domain_rating'];
            } else {
                $ahrefs_data[$domain]['dr'] = -1;
            }
            $bar->advance();

        }
        $bar->finish();
        $this->info("\n");

        //End Ahrefs DR

        //Ahrefs In/Out Links
        $this->info("Ahrefs In/Out Links");
        $bar = $this->output->createProgressBar(count($domains));
        $bar->start();

        foreach ($domains as $domain) {
            $result = $ahrefs->makeRequest('getDomainLinks',[$domain]);
            if($result) {
                $ahrefs_data[$domain]['inlinks'] = current($result)["metrics"]["refdomains"];
                //$ahrefs_data[$domain]['inlinks'] = current($result)["metrics"]["refpages"];
            } else {
                $ahrefs_data[$domain]['inlinks'] = -1;
            }
            $bar->advance();

        }
        $bar->finish();
        $this->info("\n");
        //End Ahrefs In/Out Links

        //Import into DB
        $this->info("Updating DB");

        $bar = $this->output->createProgressBar(count($ahrefs_data ));
        $bar->start();

       foreach ($ahrefs_data as $ahrefs_domain_name => $ahrefs_domain_data ) {
            DB::table($domains_table)->where('url',$ahrefs_domain_name)->update(['ahrefs_dr' => $ahrefs_domain_data['dr'],'ahrefs_inlinks' => $ahrefs_domain_data['inlinks']]);
            $bar->advance();
        }
        $bar->finish();
        $this->info("\n");

        $this->info("Process complete");

    }

}
