<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Log;
use App\Models\{
    Domains,
    Rotapost
};

class RotapostCommand extends Command {

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'domains:rotapost';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';
    protected $apikey;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct() {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle() {
        if ($this->login()) {
            $this->line('Auth successful');
            $sites = $this->getSites();



            $counter = array(
                'current' => 0,
                'total' => count($sites->Sites->BuySite),
                'new' => 0,
                'updated' => 0,
            );

            foreach ($sites->Sites->BuySite as $site) {
                $data = [];
                $url = (string) $site->Url;
                $url = mb_strtolower($url);

                $data['placement_price'] = (float) $site->PostPrice;
                $data['writing_price'] = (float) $site->PressReleasePrice;
                $data['theme'] = (string) $site->Category;
                $data['google_index'] = (int) $site->PagesInGoogle;

                if ($domain = Domains::where('url', $url)->first()) {
                    $data['domain_id'] = $domain->id;
                } else {
                    $domain = Domains::insertGetId(['url' => $url, 'created_at' => date('Y-m-d H:i:s')]);
                    $data['domain_id'] = $domain;
                }

                if (Rotapost::where('domain_id', $data['domain_id'])->first()) {
                    Rotapost::where('domain_id', $data['domain_id'])->update($data);
                    $counter['updated']++;
                } else {
                    $data['updated_at'] = date('Y-m-d H:i:s');
                    Rotapost::insert($data);
                    $counter['new']++;
                }

                $counter['current']++;

                $this->line('Rotapost | Progress: '.$counter['current'].'/'.$counter['total'].' | Added total : ' . $counter['new'] . ' | Updated total : ' . $counter['updated']);
            }

            $this->call('domains:finalize', [
                '--table' => (new Rotapost())->getTable()
            ]);

        }
    }

    private function login() {
        $url = env('ROTAPOST_LOGIN_URL') . '?Login=' . env('ROTAPOST_LOGIN') . '&AuthToken=' . md5(env('ROTAPOST_LOGIN') . env('ROTAPOST_PASSWORD'));
        $xml = simplexml_load_file($url);
        if ((string) $xml->ApiKey) {
            $this->apikey = (string) $xml->ApiKey;
            return $this->apikey;
        } else {
            return false;
        }
    }

    private function getSites() {
        $url = env('ROTAPOST_SITES_URL') . $this->apikey;
        $xml = simplexml_load_file($url);
        return $xml;
    }

}
