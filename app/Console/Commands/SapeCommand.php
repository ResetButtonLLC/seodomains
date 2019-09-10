<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Log;
use App\Models\Domains;
use PhpXmlRpc\Value;
use PhpXmlRpc\Request;
use PhpXmlRpc\Client;

class SapeCommand extends Command {

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'domains:sape';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';
    protected $client;
    protected $accountId;

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
            $page = 1;
            $added = 0;
            while ($domains = $this->getDomains($page)) {
                foreach ($domains as $domain) {
                    $data = ['source' => 'sape'];
                    $data['url'] = $domain['url']['string'];
                    $data['placement_price'] = $domain['price']['double'];
                    $data['google_index'] = $domain['nof_pages_in_google']['int'];
                    if (!Domains::where('url', $domain['url']['string'])->where('source', 'sape')->first()) {
                        Domains::insert($data);
                        $added++;
                    } else {
                        Domains::where('url', $domain['url']['string'])->where('source', 'sape')->update($data);
                    }
                }
                echo 'Domains from sape.ru page ' . $page . ' added: ' . $added . PHP_EOL;
                $page++;
                sleep(15);
            }
        }
    }

    private function login() {
        $this->client = new Client("/xmlrpc/", "api.pr.sape.ru", 80);
        $resp = $this->client->send(new Request('sape_pr.login', [new Value(env('SAPE_LOGIN')), new Value(env('SAPE_TOKEN'))]));
        if ($resp->errno > 0) {
            return false;
        } else {
            $this->accountId = $resp->value();
            $cookies = $resp->cookies();
            $this->client->setcookie('PR', $resp->cookies()["PR"]["value"]);
            return $this->accountId;
        }
    }

    private function getDomains($page = 1) {
        $resp = $this->client->send(new Request('sape_pr.site.search', [new Value('news', 'string'), new Value([], 'struct'), new Value($page, 'int'), new Value(10, 'int')]));

        if (!$resp->value()) {
            return false;
        } else {
            return $resp->value();
        }
    }

}
