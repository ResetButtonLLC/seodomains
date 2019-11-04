<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Log;
use App\Models\{
    Domains,
    Sape
};
use Carbon\Carbon;
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
            $updated = 0;

            while ($domains = $this->sapeGetSitesFromPage($page, 500)) {


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
                        Sape::where('domain_id', $data['domain_id'])->update($data);
                        $updated++;
                    } else {
                        Sape::insert($data);
                        $data['updated_at'] = date('Y-m-d H:i:s');
                        $added++;
                    }
                }
                $this->line('Sape.ru page : ' . $page . ' | Fetched domains : ' . count($domains).' | Added total :  '.$added.' | Updated total : '.$updated.' | Sleeping for 15 seconds');
                $page++;
                sleep(15);
            }

            $this->call('domains:finalize', [
                '--table' => (new Sape())->getTable()
            ]);

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

    private function sapeGetSitesFromPage($page, $domains_per_request = 10)
    {
        $domains = array();

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
                        <value><i4>'.$domains_per_request.'</i4></value>
                     </param>
                  </params>
            </methodCall>
        ';

        $resp = simplexml_load_string($this->makeRequest($payload));

        foreach ($resp->params->param->value->array->data->value as $entry) {
            $domain_data = $entry->struct->member;

            $id = current($domain_data[0]->value->int);

            $domains[$id]['url']['string'] = current($domain_data[1]->value->string[0]);
            //URLS=>DOMAINS
            $domains[$id]['url']['string'] = str_ireplace('https://','',$domains[$id]['url']['string']);
            $domains[$id]['url']['string'] = str_ireplace('http://','',$domains[$id]['url']['string']);
            $domains[$id]['url']['string'] = preg_replace ( '/^www\./', '', $domains[$id]['url']['string']);
            $domains[$id]['url']['string'] = idn_to_utf8($domains[$id]['url']['string'],IDNA_DEFAULT,INTL_IDNA_VARIANT_UTS46); //punycode
            //URLS=>DOMAINS done

            $domains[$id]['price']['double'] = intval (current ($domain_data[2]->value->double[0]));
            $domains[$id]['nof_pages_in_google']['int'] = intval (current ($domain_data[14]->value->int[0]));

        }

        //file_put_contents(public_path('sites/sape/responce.txt'),print_r($resp->params->param->value->array->data ,true));

        return $domains;
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
