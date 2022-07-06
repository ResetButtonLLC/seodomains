<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Models\{
    Domains,
    Miralinks
};

class MiralinksCommand extends ParserCommand {

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'domains:miralinks';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

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

        $this->initLog('miralinks');

        $counter = array(
            'current' => 0,
            'total' => 0,
            'new' => 0,
            'updated' => 0,
        );

        $homepage_html = $this->login();

        if (!$homepage_html) {
            die();
        }


       //file_put_contents($this->logfolder.'/login.html',$homepage_html);

        $start = 0;
        $counter = array(
            'current' => 0,
            'total' => 0,
            'new' => 0,
            'updated' => 0,
        );

        while ($data = $this->getData($start)) {

            //Progressbar init
            if ($start == 0) {
                $counter['total'] = $data->iTotalRecords;
            }

            if (count($data->aaData) > 0) {
                $counter['current'] = $counter['current']+count($data->aaData);

                foreach ($data->aaData as $domain) {

                    $lang = json_decode($domain->rowData->langCode);

                    if (isset($lang[0][0])) {
                        $lang = $lang[0][0];
                    } else {
                        $lang = null;
                    }
                    $url = mb_strtolower($domain->rowData->{"Ground.folder_url_wl"});

                    $info = [
                        'name' => $domain->rowData->{"Ground.name"},
                        'site_id' => $domain->rowData->{"Ground.id"},
                        'desc' => $domain->rowData->{"Ground.description"},
                        'placement_price_usd' => $domain->rowData->{"Ground.price_usd"},
                        'writing_price_usd' => $domain->rowData->{"Ground.article_price_usd"},
                        'placement_price' => $domain->rowData->{"Ground.price_rur"},
                        'writing_price' => $domain->rowData->{"Ground.article_price_rur"}+$domain->rowData->{"Ground.price_rur"},
                        'region' => $domain->rowData->{"Region.title"},
                        'theme' => $domain->rowData->subj,
                        'google_index' => $domain->rowData->{"Ground.google_indexed_count"},
                        'links' => $domain->rowData->{"Ground.links_in_articles"},
                        'lang' => $lang,
                        'traffic' => $domain->rowData->{"traffic.value"},
                        'majestic_tf' => $domain->rowData->{"Ground.tf"},
                        'majestic_cf' => $domain->rowData->{"Ground.cf"},
                        'last_placement' => isset($domain->rowData->{"last_placement_str"}) ? $domain->rowData->{"last_placement_str"} : '1',
                        'placement_time' => isset($domain->rowData->{"placement_time_str"}) ? $domain->rowData->{"placement_time_str"} : '1',
                     ];

                    if ($domain = Domains::where('url', $url)->first()) {
                        $info['domain_id'] = $domain->id;
                    } else {
                        $domain = Domains::insertGetId(['url' => $url, 'created_at' => date('Y-m-d H:i:s')]);
                        $info['domain_id'] = $domain;
                    }

                    if (Miralinks::where('domain_id', $info['domain_id'])->first()) {
                        Miralinks::where('domain_id', $info['domain_id'])->update($info);
                        $counter['updated']++;
                    } else {
                        //Добавляем updated_at при создании, чтоб в конце обновления удалить домены у которых updated_at отличается на Х часов от времени обновления
                        $info['updated_at'] = date('Y-m-d H:i:s');
                        Miralinks::insert($info);
                        $counter['new']++;
                    }
                }
                $antiban_pause = mt_rand(20, 30);
                $this->writeLog('Miralinks.ru | Fetched domains : ' . count($data->aaData) . ' | Progress: '.$counter['current'].'/'.$counter['total'].' | Added total : ' . $counter['new'] . ' | Updated total : ' . $counter['updated']. ' | Sleeping '.$antiban_pause. ' seconds');
                sleep($antiban_pause);
                $start += 50;

            } else {

                $this->writeLog('Exporting Majestic CF/TF to main table');

                /*
                 * todo
                DB::table('domains')
                    ->join('miralinks', 'id', '=', 'miralinks.domain_id')
                    ->update(['majestic_cf' => 'miralinks.majestic_cf'])
                    ->whereNotNull('miralinks.majestic_cf')
                    ->get();
                */

                //Через Query builder не работает
                DB::statement('
                  UPDATE domains 
                  INNER JOIN miralinks ON domains.id = miralinks.domain_id 
                  SET domains.majestic_cf = miralinks.majestic_cf, domains.majestic_updated = NOW() 
                  WHERE miralinks.majestic_cf IS NOT NULL;
                  ');

                            DB::statement('
                  UPDATE domains 
                  INNER JOIN miralinks ON domains.id = miralinks.domain_id 
                  SET domains.majestic_tf = miralinks.majestic_tf, domains.majestic_updated = NOW() 
                  WHERE miralinks.majestic_tf IS NOT NULL;
                  ');

                $this->call('domains:finalize', [
                    '--table' => (new Miralinks())->getTable()
                ]);

                break;

            }
        }

    }

    private function login() {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://www.miralinks.ru');
        curl_setopt($ch, CURLOPT_REFERER, 'https://www.miralinks.ru');
        curl_setopt($ch, CURLOPT_PROXY, 'p.webshare.io');
        curl_setopt($ch, CURLOPT_PROXYPORT, '9999');
        curl_setopt($ch, CURLOPT_COOKIEJAR, $this->getCookie());
        curl_setopt($ch, CURLOPT_COOKIEFILE, $this->getCookie());
        curl_setopt($ch, CURLOPT_ENCODING, "");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/47.0.2526.106 Safari/537.36");
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            "Connection: keep-alive",
            "Content-Type: application/x-www-form-urlencoded",
            "Postman-Token: 35270faf-569e-4a30-a508-1dfb926ccc94,5a3d62cb-fc51-4915-96cf-452f2b0569f7",
            "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/77.0.3865.120 Safari/537.36",
            "accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3",
            "accept-language: ru-RU,ru;q=0.9,en-US;q=0.8,en;q=0.7,uk;q=0.6",
            "cache-control: no-cache,no-cache",
            "referer: https://www.miralinks.ru/users/login,https://www.miralinks.ru/",
            "sec-fetch-site: same-origin",
            "upgrade-insecure-requests: 1"
        ));

        $html = curl_exec($ch);
        curl_close($ch);

        if (strpos($html, 'Ваши проекты')) {
            $this->writeLog('Auth successful');
            return $html;
        } else {
            $this->writeHtmlLogFile('error_login.html', $html);
            $this->writeLog('Login not successful : check cookies');
            return '';
        }

    }

    private function getData($start = 0) {

        $response = '';

        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => "https://www.miralinks.ru/ajaxPort/loadDataTableDataCatalog",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_COOKIEJAR => $this->getCookie(),
            CURLOPT_COOKIEFILE => $this->getCookie(),
            CURLOPT_POSTFIELDS => "sEcho=3&iColumns=33&sColumns=&iDisplayStart=" . $start . "&iDisplayLength=50&mDataProp_0=0&mDataProp_1=1&mDataProp_2=2&mDataProp_3=3&mDataProp_4=4&mDataProp_5=5&mDataProp_6=6&mDataProp_7=7&mDataProp_8=8&mDataProp_9=9&mDataProp_10=10&mDataProp_11=11&mDataProp_12=12&mDataProp_13=13&mDataProp_14=14&mDataProp_15=15&mDataProp_16=16&mDataProp_17=17&mDataProp_18=18&mDataProp_19=19&mDataProp_20=20&mDataProp_21=21&mDataProp_22=22&mDataProp_23=23&mDataProp_24=24&mDataProp_25=25&mDataProp_26=26&mDataProp_27=27&mDataProp_28=28&mDataProp_29=29&mDataProp_30=30&mDataProp_31=31&mDataProp_32=32&sSearch=&bRegex=false&sSearch_0=&bRegex_0=false&bSearchable_0=true&sSearch_1=&bRegex_1=false&bSearchable_1=true&sSearch_2=&bRegex_2=false&bSearchable_2=true&sSearch_3=&bRegex_3=false&bSearchable_3=true&sSearch_4=&bRegex_4=false&bSearchable_4=true&sSearch_5=&bRegex_5=false&bSearchable_5=true&sSearch_6=&bRegex_6=false&bSearchable_6=true&sSearch_7=&bRegex_7=false&bSearchable_7=true&sSearch_8=&bRegex_8=false&bSearchable_8=true&sSearch_9=&bRegex_9=false&bSearchable_9=true&sSearch_10=&bRegex_10=false&bSearchable_10=true&sSearch_11=&bRegex_11=false&bSearchable_11=true&sSearch_12=&bRegex_12=false&bSearchable_12=true&sSearch_13=&bRegex_13=false&bSearchable_13=true&sSearch_14=&bRegex_14=false&bSearchable_14=true&sSearch_15=&bRegex_15=false&bSearchable_15=true&sSearch_16=&bRegex_16=false&bSearchable_16=true&sSearch_17=&bRegex_17=false&bSearchable_17=true&sSearch_18=&bRegex_18=false&bSearchable_18=true&sSearch_19=&bRegex_19=false&bSearchable_19=true&sSearch_20=&bRegex_20=false&bSearchable_20=true&sSearch_21=&bRegex_21=false&bSearchable_21=true&sSearch_22=&bRegex_22=false&bSearchable_22=true&sSearch_23=&bRegex_23=false&bSearchable_23=true&sSearch_24=&bRegex_24=false&bSearchable_24=true&sSearch_25=&bRegex_25=false&bSearchable_25=true&sSearch_26=&bRegex_26=false&bSearchable_26=true&sSearch_27=&bRegex_27=false&bSearchable_27=true&sSearch_28=&bRegex_28=false&bSearchable_28=true&sSearch_29=&bRegex_29=false&bSearchable_29=true&sSearch_30=&bRegex_30=false&bSearchable_30=true&sSearch_31=&bRegex_31=false&bSearchable_31=true&sSearch_32=&bRegex_32=false&bSearchable_32=true&iSortCol_0=2&sSortDir_0=desc&iSortCol_1=3&sSortDir_1=desc&iSortingCols=2&bSortable_0=false&bSortable_1=true&bSortable_2=true&bSortable_3=true&bSortable_4=true&bSortable_5=true&bSortable_6=true&bSortable_7=true&bSortable_8=true&bSortable_9=true&bSortable_10=true&bSortable_11=true&bSortable_12=true&bSortable_13=true&bSortable_14=true&bSortable_15=true&bSortable_16=true&bSortable_17=true&bSortable_18=true&bSortable_19=true&bSortable_20=true&bSortable_21=true&bSortable_22=true&bSortable_23=true&bSortable_24=true&bSortable_25=true&bSortable_26=true&bSortable_27=true&bSortable_28=true&bSortable_29=true&bSortable_30=true&bSortable_31=true&bSortable_32=true&tsDataTableType=with_checking&tsDataTableConfigType=dataTable.Catalog&searchData=%7B%22s_catalog_type%22%3A%22google%22%7D",
            CURLOPT_HTTPHEADER => array(
                "Accept: application/json, text/javascript, */*; q=0.01",
                "Accept-Encoding: gzip, deflate",
                "Accept-Language: ru-RU,ru;q=0.8,en-US;q=0.5,en;q=0.3",
                "Cache-Control: no-cache",
                "Connection: keep-alive",
                "Content-Length: 2928",
                "Content-Type: application/x-www-form-urlencoded; charset=UTF-8",
                //"Cookie: __ddg_=12018; _ym_uid=15657717131008035131; _ym_d=1565771713; _ga=GA1.2.123330757.1565771713; _gid=GA1.2.1146967899.1565771713; Miralinks[someunknownvalue]=Q2FrZQ%3D%3D.5NtwjzBfU4%2F0Flh9xmDGq7Ibzv6ynoXHP7DJ15mBQek%3D; MIRALINKS_LIVE=68853a65bb3e1c68df4842d08de56697; Miralinks[changeWm]=Q2FrZQ%3D%3D.4w%3D%3D; _ym_isad=2; Miralinks[sometopsecretvalue]=Q2FrZQ%3D%3D.voItjykeGdegTA532T%2FF6%2BgFmKq1y4DHPuXJg8uATus8tmSI2lY0kTCj7OA05Sz2Urs%3D; Miralinks[guest3]=Q2FrZQ%3D%3D.54p1iz8lAM35byBvxyby77IQn%2B2xwo3DMOWezuDPT9U%3D; _ym_visorc_54215566=w; _gat_gtag_UA_142755255_1=1",
                "Host: www.miralinks.ru",
                "Referer: https://www.miralinks.ru/catalog?s_catalog_type=google",
                "TE: Trailers",
                "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:67.0) Gecko/20100101 Firefox/67.0",
                "X-Requested-With: XMLHttpRequest",
                "cache-control: no-cache"
            ),
        ));


        while(!$response) {
            $response = curl_exec($curl);
            $this->writeHtmlLogFile($start.'.html', $response);
            //file_put_contents($this->logfolder.'/'.$start.'.html',$response);
            if (!json_decode($response)) {
                $antiban_pause = mt_rand(30, 50);
                $this->writeLog('Miralinks.ru | Get empty responce | sleeping for '.$antiban_pause.' seconds');
                sleep($antiban_pause);
            }
        }

        $this->writeHtmlLogFile($start.'.html', $response);
        $err = curl_error($curl);

        curl_close($curl);

        return json_decode($response);
    }

}
