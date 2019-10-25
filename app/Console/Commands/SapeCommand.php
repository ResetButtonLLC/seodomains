<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Log;
use App\Models\{
    Domains,
    Sape
};
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
    protected $auth_cookie;

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

        if ($this->sapeAuth()) {
            $page = 1;
            $added = 0;

            while ($domains = $this->sapeGetSitesFromPage($page)) {

                print_r(count($domains));
                die();

                foreach ($domains as $domain) {
                    $url = $domain['url']['string'];
                    $data['placement_price'] = $domain['price']['double'];
                    $data['google_index'] = $domain['nof_pages_in_google']['int'];

                    if ($domain = Domains::where('url', $url)->first()) {
                        $data['domain_id'] = $domain->id;
                    } else {
                        $domain = Domains::insertGetId(['url' => $url, 'created_at' => date('Y-m-d H:i:s')]);
                        $data['domain_id'] = $domain;
                    }

                    if (Sape::where('domain_id', $data['domain_id'])->first()) {
                        $data['updated_at'] = date('Y-m-d H:i:s');
                        Sape::where('domain_id', $data['domain_id'])->update($data);
                    } else {
                        $data['created_at'] = date('Y-m-d H:i:s');
                        Sape::insert($data);
                        $added++;
                    }
                }
                $this->line('Domains from sape.ru page ' . $page . ' added: ' . $added);
                $page++;
                sleep(15);
            }
        }
    }

    private function login() {
        $this->client = new Client("/xmlrpc/", "api.pr.sape.ru", 80);
        $resp = $this->client->send(new Request('sape_pr.login', [new Value(env('SAPE_LOGIN')), new Value(env('SAPE_TOKEN'))]));

        if ($resp->errno > 0) {
            $this->error('Auth not successful : saving responce to '.url('sites/sape/auth.txt').PHP_EOL);
            file_put_contents(public_path('sites/sape/auth.txt'),$resp);
            return false;
        } else {
            $this->accountId = $resp->value();
            $cookies = $resp->cookies();
            $this->client->setcookie('PR', $resp->cookies()["PR"]["value"]);
            $this->line('Auth successfull');
            return $this->accountId;
        }
    }

    private function getDomains($page = 1) {



        $resp = $this->client->send(new Request('sape_pr.site.search', [new Value('news', 'string'), new Value([], 'struct'), new Value($page, 'int'), new Value(50, 'int')]));

        //print_r($resp);

        if (!$resp->value()) {
            return false;
        } else {
            return $resp->value();
        }
    }

    private function sapeAuth()
    {
        $payload='<?xml version="1.0"?>
            <methodCall>
               <methodName>sape_pr.login</methodName>
                  <params>
                     <param>
                        <value><string>'.env('SAPE_LOGIN').'</string></value>
                     </param>
                     <param>
                        <value><string>'.env('SAPE_TOKEN').'</string></value>
                     </param>
                  </params>
            </methodCall>
        ';

        $resp = $this->makeRequest($payload);

        $result = simplexml_load_string($resp);

        if (isset($result->params->param->value->int)) {
            $this->line('Auth successful');

            return true;
        } else {
            $this->error('Responce not successful : saving responce to '.url('sites/sape/auth.txt').PHP_EOL);
            file_put_contents(public_path('sites/sape/auth.txt'),$resp);
            return false;
        }

    }

    private function sapeGetSitesFromPage($page)
    {
        $payload='<?xml version="1.0"?>
            <methodCall>
               <methodName>sape_pr.site.search</methodName>
                  <params>
                     <param>
                        <value><string>news</string></value>
                     </param>
                     <param>
                        <value>
                            <struct></struct>
                         </value>
                     </param>
                     <param>
                        <value><i4>'.$page.'</i4></value>
                     </param>
                     <param>
                        <value><i4>1</i4></value>
                     </param>
                  </params>
            </methodCall>
        ';

        $resp = $this->makeRequest($payload);

        $result = simplexml_import_dom($resp);


        file_put_contents(public_path('sites/sape/responce.txt'),print_r($result,true));


        print_r($result);
        die();

    }


    private function makeRequest($payload)
    {
        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => "http://api.pr.sape.ru/xmlrpc/",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_POST => true,
            CURLOPT_COOKIEJAR => public_path(env('SAPE_COOKIE_FILE')),
            CURLOPT_COOKIEFILE => public_path(env('SAPE_COOKIE_FILE')),
            CURLOPT_POSTFIELDS => $payload,
            CURLOPT_HTTPHEADER => array(
                "Content-Type: text/plain",
            ),
        ));

        $response = curl_exec($curl);
        curl_close($curl);

        return $response;

    }


}
