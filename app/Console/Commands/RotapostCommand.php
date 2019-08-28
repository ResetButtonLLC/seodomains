<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Log;
use App\Models\Domains;

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
            $sites = $this->getSites();
            $added = 0;
            
            foreach ($sites->Sites->BuySite as $site) {
                $data = [];
                $data['url'] = (string) $site->Url;
                $data['placement_price'] = (float) $site->PostPrice;
                $data['writing_price'] = (float) $site->PressReleasePrice;
                $data['theme'] = (string) $site->Category;
                $data['google_index'] = (int) $site->PagesInGoogle;
                if (!Domains::where('url', $data['url'])->where('source', 'rotapost')->first()) {
                    $data['source'] = 'rotapost';
                    Domains::insert($data);
                    $added++;
                }
            }
            echo 'Domains added from rotapost.ru: ' . $added . PHP_EOL;
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
