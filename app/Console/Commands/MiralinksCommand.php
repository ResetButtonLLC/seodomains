<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Log;
use App\Models\{
    Domains,
    Miralinks
};

class MiralinksCommand extends Command {

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

        var_dump($this->login());
        die();

        if ($this->login()) {
            $start = 0;
            while ($data = $this->getData($start)) {

                if (count($data->aaData) > 0) {
                    foreach ($data->aaData as $domain) {
                        dd($domain);
                        $lang = json_decode($domain->rowData->langCode);
                        if (isset($lang[0][0])) {
                            $lang = $lang[0][0];
                        } else {
                            $lang = null;
                        }
                        $url = $domain->rowData->{"Ground.folder_url_wl"};
                        $info = ['name' => $domain->rowData->{"Ground.name"}, 'site_id' => $domain->rowData->{"Ground.id"}, 'desc' => $domain->rowData->{"Ground.description"}, 'placement_price_usd' => $domain->rowData->{"Ground.price_usd"}, 'writing_price_usd' => $domain->rowData->{"Ground.article_price_usd"}, 'placement_price' => $domain->rowData->{"Ground.price_rur"}, 'writing_price' => $domain->rowData->{"Ground.article_price_rur"}, 'region' => $domain->rowData->{"Region.title"}, 'theme' => $domain->rowData->subj, 'google_index' => $domain->rowData->{"Ground.google_indexed_count"}, 'links' => $domain->rowData->{"Ground.links_in_articles"}, 'lang' => $lang, 'traffic' => $domain->rowData->{"traffic.value"}];
                        if ($domain = Domains::where('url', $url)->first()) {
                            $info['domain_id'] = $domain->id;
                        } else {
                            $domain = Domains::insertGetId(['url' => $url, 'created_at' => date('Y-m-d H:i:s')]);
                            $info['domain_id'] = $domain;
                        }

                        if (Miralinks::where('domain_id', $info['domain_id'])->first()) {
                            $info['updated_at'] = date('Y-m-d H:i:s');
                            Miralinks::where('domain_id', $info['domain_id'])->update($info);
                        } else {
                            $info['created_at'] = date('Y-m-d H:i:s');
                            Miralinks::insert($info);
                        }
                    }
                    $start += 50;
                } else {
                    break;
                }
            }
        }
    }

    private function login() {
        $ch = curl_init();
        if (file_exists(public_path(env('MIRALINKS_COOKIE_FILE')))) {
            unlink(public_path(env('MIRALINKS_COOKIE_FILE')));
        }

        curl_setopt($ch, CURLOPT_URL, env('MIRALINKS_LOGIN_URL'));
        curl_setopt($ch, CURLOPT_REFERER, env('MIRALINKS_LOGIN_URL'));
        curl_setopt($ch, CURLOPT_COOKIEJAR, public_path(env('MIRALINKS_COOKIE_FILE')));
        curl_setopt($ch, CURLOPT_COOKIEFILE, public_path(env('MIRALINKS_COOKIE_FILE')));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/47.0.2526.106 Safari/537.36");

        $html = curl_exec($ch);

        if (strpos($html, 'Ваши проекты')) {
            return true;
        } else {
            $this->error('Login not successful : saving page to '.url('/sites/miralinks/login.html').PHP_EOL);
            file_put_contents(public_path('sites/miralinks/login.html'),$html);
            return false;
        }

        $postinfo = "_method=POST"
                . "&" . urlencode("data[User][login]") . "=" . env('MIRALINKS_USERNAME')
                . "&" . urlencode("data[User][password]") . "=" . env('MIRALINKS_PASSWORD')
                . "&" . urlencode("data[User][remember]") . "=on";

        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postinfo);

        $html = curl_exec($ch);
        curl_close($ch);

        if (strpos($html, 'Ваши проекты')) {
            return true;
        } else {
            Log::info($html);
            return false;
        }
    }

    private function getData($start = 0) {
        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => "https://www.miralinks.ru/ajaxPort/loadDataTableDataCatalog",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_POSTFIELDS => "sEcho=3&iColumns=33&sColumns=&iDisplayStart=" . $start . "&iDisplayLength=50&mDataProp_0=0&mDataProp_1=1&mDataProp_2=2&mDataProp_3=3&mDataProp_4=4&mDataProp_5=5&mDataProp_6=6&mDataProp_7=7&mDataProp_8=8&mDataProp_9=9&mDataProp_10=10&mDataProp_11=11&mDataProp_12=12&mDataProp_13=13&mDataProp_14=14&mDataProp_15=15&mDataProp_16=16&mDataProp_17=17&mDataProp_18=18&mDataProp_19=19&mDataProp_20=20&mDataProp_21=21&mDataProp_22=22&mDataProp_23=23&mDataProp_24=24&mDataProp_25=25&mDataProp_26=26&mDataProp_27=27&mDataProp_28=28&mDataProp_29=29&mDataProp_30=30&mDataProp_31=31&mDataProp_32=32&sSearch=&bRegex=false&sSearch_0=&bRegex_0=false&bSearchable_0=true&sSearch_1=&bRegex_1=false&bSearchable_1=true&sSearch_2=&bRegex_2=false&bSearchable_2=true&sSearch_3=&bRegex_3=false&bSearchable_3=true&sSearch_4=&bRegex_4=false&bSearchable_4=true&sSearch_5=&bRegex_5=false&bSearchable_5=true&sSearch_6=&bRegex_6=false&bSearchable_6=true&sSearch_7=&bRegex_7=false&bSearchable_7=true&sSearch_8=&bRegex_8=false&bSearchable_8=true&sSearch_9=&bRegex_9=false&bSearchable_9=true&sSearch_10=&bRegex_10=false&bSearchable_10=true&sSearch_11=&bRegex_11=false&bSearchable_11=true&sSearch_12=&bRegex_12=false&bSearchable_12=true&sSearch_13=&bRegex_13=false&bSearchable_13=true&sSearch_14=&bRegex_14=false&bSearchable_14=true&sSearch_15=&bRegex_15=false&bSearchable_15=true&sSearch_16=&bRegex_16=false&bSearchable_16=true&sSearch_17=&bRegex_17=false&bSearchable_17=true&sSearch_18=&bRegex_18=false&bSearchable_18=true&sSearch_19=&bRegex_19=false&bSearchable_19=true&sSearch_20=&bRegex_20=false&bSearchable_20=true&sSearch_21=&bRegex_21=false&bSearchable_21=true&sSearch_22=&bRegex_22=false&bSearchable_22=true&sSearch_23=&bRegex_23=false&bSearchable_23=true&sSearch_24=&bRegex_24=false&bSearchable_24=true&sSearch_25=&bRegex_25=false&bSearchable_25=true&sSearch_26=&bRegex_26=false&bSearchable_26=true&sSearch_27=&bRegex_27=false&bSearchable_27=true&sSearch_28=&bRegex_28=false&bSearchable_28=true&sSearch_29=&bRegex_29=false&bSearchable_29=true&sSearch_30=&bRegex_30=false&bSearchable_30=true&sSearch_31=&bRegex_31=false&bSearchable_31=true&sSearch_32=&bRegex_32=false&bSearchable_32=true&iSortCol_0=2&sSortDir_0=desc&iSortCol_1=3&sSortDir_1=desc&iSortingCols=2&bSortable_0=false&bSortable_1=true&bSortable_2=true&bSortable_3=true&bSortable_4=true&bSortable_5=true&bSortable_6=true&bSortable_7=true&bSortable_8=true&bSortable_9=true&bSortable_10=true&bSortable_11=true&bSortable_12=true&bSortable_13=true&bSortable_14=true&bSortable_15=true&bSortable_16=true&bSortable_17=true&bSortable_18=true&bSortable_19=true&bSortable_20=true&bSortable_21=true&bSortable_22=true&bSortable_23=true&bSortable_24=true&bSortable_25=true&bSortable_26=true&bSortable_27=true&bSortable_28=true&bSortable_29=true&bSortable_30=true&bSortable_31=true&bSortable_32=true&tsDataTableType=with_checking&tsDataTableConfigType=dataTable.Catalog&searchData=%7B%22s_catalog_type%22%3A%22google%22%7D",
            CURLOPT_HTTPHEADER => array(
                "Accept: application/json, text/javascript, */*; q=0.01",
                "Accept-Encoding: gzip, deflate",
                "Accept-Language: ru-RU,ru;q=0.8,en-US;q=0.5,en;q=0.3",
                "Cache-Control: no-cache",
                "Connection: keep-alive",
                "Content-Length: 2928",
                "Content-Type: application/x-www-form-urlencoded; charset=UTF-8",
                "Cookie: __ddg_=12018; _ym_uid=15657717131008035131; _ym_d=1565771713; _ga=GA1.2.123330757.1565771713; _gid=GA1.2.1146967899.1565771713; Miralinks[someunknownvalue]=Q2FrZQ%3D%3D.5NtwjzBfU4%2F0Flh9xmDGq7Ibzv6ynoXHP7DJ15mBQek%3D; MIRALINKS_LIVE=68853a65bb3e1c68df4842d08de56697; Miralinks[changeWm]=Q2FrZQ%3D%3D.4w%3D%3D; _ym_isad=2; Miralinks[sometopsecretvalue]=Q2FrZQ%3D%3D.voItjykeGdegTA532T%2FF6%2BgFmKq1y4DHPuXJg8uATus8tmSI2lY0kTCj7OA05Sz2Urs%3D; Miralinks[guest3]=Q2FrZQ%3D%3D.54p1iz8lAM35byBvxyby77IQn%2B2xwo3DMOWezuDPT9U%3D; _ym_visorc_54215566=w; _gat_gtag_UA_142755255_1=1",
                "Host: www.miralinks.ru",
                "Referer: https://www.miralinks.ru/catalog?s_catalog_type=google",
                "TE: Trailers",
                "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:67.0) Gecko/20100101 Firefox/67.0",
                "X-Requested-With: XMLHttpRequest",
                "cache-control: no-cache"
            ),
        ));

        $response = curl_exec($curl);
        $err = curl_error($curl);

        curl_close($curl);


        return json_decode($response);
    }

}
