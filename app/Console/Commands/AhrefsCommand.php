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

        $domains_urls = DB::table((new Domains)->getTable())->select('url')->get();

        foreach ($domains_urls as $domain) {
            $domains[] = $domain->url;
        }

        $this->info('Get '.count($domains).' domains from database');

        $domains = array_slice($domains,0,10); //DEBUG

        $ahrefs = new ApiPromodoHelper();
        //Ahrefs DR

        $this->info('Asking Ahrefs for DR');
        $bar = $this->output->createProgressBar(count($domains));
        $bar->start();

        foreach ($domains as $domain) {
            $result = $ahrefs->makeRequest('getDomainRating',[$domain]);
            if($result) {
                $ahrefs_data[$domain] = current($result);
            }
            $bar->advance();

        }
        $bar->finish();
        //End Ahrefs DR

        //todo Ahrefs In/Out Links

        print_r($ahrefs_data);

    }

}
