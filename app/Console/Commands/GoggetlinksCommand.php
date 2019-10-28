<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Log;
use App\Models\{
    Domains,
    Gogetlinks
};

class GoggetlinksCommand extends Command {

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'domains:gogetlinks';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Load domains from gogetlinks.net';
    protected $count;

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
    public function handle()
    {

        $this->checkLogin();

        $page = 0;
        $url = "/<a ?.*>(.*)<\/a>/";
        $traffik = '/(.*)<\/td>/';
        $price = '/value="(.*)"><\/label>/';
        $quantity = "/(.*)<\/td>/";
        $added = 0;

        //set 50 sites per page
        //file_put_contents(public_path(env('GOGETLINKS_COOKIE_FILE')),'gogetlinks.net	FALSE	/	FALSE	1602936216	in_page_search_sites	50'.PHP_EOL, FILE_APPEND);

        while ($data = $this->getData($page)) {

            //Antiban pause

            if ($page) {
                sleep(mt_rand(30,50));
            }


            //Progressbar init
            if ($page == 0) {
                preg_match('/Найдено\ (\d{1,6})\ сайт/si',$data, $matches);
                $domains = trim($matches[1]);
                $bar = $this->output->createProgressBar($domains);
                $bar->start();
            }

            $data = explode('<tbody id="body_table_content">', $data);

            if (!$this->count) {
                $lines = explode('<td', $data[0]);
                preg_match($quantity, $lines[1], $matches);
                $this->count = preg_replace('/\D/', '', $matches[1]);
            }

            if ($added >= $this->count) {
                break;
            }

            $row = explode('search_sites_row', $data[1]);
            unset($row[0]);

            //print_r(count($row));
            //die();

            foreach ($row as $col) {
                $data = [];
                $values = explode('<td', $col);

                preg_match($url, $values[1], $matches);
                if ($matches) {
                    $site = utf8_encode($matches[1]);

                    unset($matches);
                }

                preg_match($traffik, $values[4], $matches);
                if ($matches) {
                    $data['traffic'] = preg_replace('/\D/', '', $matches[1]);
                    unset($matches);
                }

                preg_match($price, $values[9], $matches);
                if ($matches) {
                    $data['placement_price'] = $matches[1];
                    unset($matches);
                }

                if ($domain = Domains::where('url', $site)->first()) {
                    $data['domain_id'] = $domain->id;
                } else {
                    $domain = Domains::insertGetId(['url' => $site, 'created_at' => date('Y-m-d H:i:s')]);
                    $data['domain_id'] = $domain;
                }

                if (Gogetlinks::where('domain_id', $data['domain_id'])->first()) {
                    $data['updated_at'] = date('Y-m-d H:i:s');
                    Gogetlinks::where('domain_id', $data['domain_id'])->update($data);
                } else {
                    $data['created_at'] = date('Y-m-d H:i:s');
                    Gogetlinks::insert($data);
                }
                $added++;

                $bar->advance();

            }

            $page++;
        }

        $bar->finish();

    }

    private function checkLogin()
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, env('GOGETLINKS_LOGIN_URL'));
        curl_setopt($ch, CURLOPT_REFERER, env('GOGETLINKS_LOGIN_URL'));
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_COOKIEJAR, public_path(env('GOGETLINKS_COOKIE_FILE')));
        curl_setopt($ch, CURLOPT_COOKIEFILE, public_path(env('GOGETLINKS_COOKIE_FILE')));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/47.0.2526.106 Safari/537.36");

        $html = curl_exec($ch);

        if (!strpos($html, 'my_campaigns.php')) {
            file_put_contents(public_path('sites/gogetlinks/login.html'),$html);
            $this->error('Login not successful : saving page to '.url('/sites/gogetlinks/login.html').PHP_EOL);
            $this->error('Most likely that the cookies has expired'.PHP_EOL);
            return;
        }

    }

    private function login() {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, env('GOGETLINKS_LOGIN_URL'));
        curl_setopt($ch, CURLOPT_REFERER, env('GOGETLINKS_LOGIN_URL'));
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_COOKIEJAR, public_path(env('GOGETLINKS_COOKIE_FILE')));
        curl_setopt($ch, CURLOPT_COOKIEFILE, public_path(env('GOGETLINKS_COOKIE_FILE')));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/47.0.2526.106 Safari/537.36");

        $html = curl_exec($ch);
        if (strpos($html, '<div class="profile">')) {
            return true;
        }

        if (!mb_strpos($html, '<input type="password"')) {
            return false;
        }

        $postinfo = "e_mail=" . env('GOGETLINKS_USERNAME')
                . "&password=" . env('GOGETLINKS_PASSWORD')
                . "&button=Войти"
                . "&remember=on";

        curl_setopt($ch, CURLOPT_URL, env('GOGETLINKS_LOGIN_URL_POST'));
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postinfo);

        $html = curl_exec($ch);
        curl_close($ch);

        if (strpos($html, 'window.location.href="/my_campaigns.php"')) {
            $this->post_settings();
            return true;
        } else {
            return false;
        }
    }

    private function getData($page = 0) {
        Log::info('Gogetlinks page: ' . $page);

        if ($page > 0) {
            $params = 'action=search&additional_action=change_count_in_page&page=' . $page;
        } else {
            $params = 'action=search';
        }

        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => "https://gogetlinks.net/search_sites.php?action=search&additional_action=change_count_in_page&recommend_sites_comp_id=0",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_COOKIEJAR => public_path(env('GOGETLINKS_COOKIE_FILE')),
            CURLOPT_COOKIEFILE => public_path(env('GOGETLINKS_COOKIE_FILE')),
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_POSTFIELDS => "compaing_id_list=583393&page=".$page."&order_by=&order_direction=desc&condition=%7B%22type_search_engine%22%3A2%2C%22is_link%22%3Atrue%2C%22is_post%22%3Atrue%2C%22is_paper%22%3Atrue%2C%22tic_from%22%3Afalse%2C%22tic_to%22%3Afalse%2C%22sqi_from%22%3Afalse%2C%22sqi_to%22%3Afalse%2C%22da_from%22%3Afalse%2C%22da_to%22%3Afalse%2C%22trust_flow%22%3Afalse%2C%22ignore_sape_links%22%3Atrue%2C%22only_exclusive%22%3Afalse%2C%22in_any_catalog%22%3Afalse%2C%22in_yandex_catalog%22%3Afalse%2C%22in_news_aggregator%22%3Afalse%2C%22reviewing_long%22%3A5%2C%22reviewing_long_na%22%3Atrue%2C%22indexation%22%3Afalse%2C%22indexation_na%22%3Atrue%2C%22from_white_list%22%3A%5B%5D%2C%22hide_black_list%22%3Atrue%2C%22ignore_rejected_sites%22%3Atrue%2C%22backreferencing%22%3Afalse%2C%22traffic_host%22%3Afalse%2C%22traffic_with_no_data%22%3Atrue%2C%22price_type%22%3A1%2C%22price_paper%22%3Afalse%2C%22price_post%22%3Afalse%2C%22price_link%22%3Afalse%2C%22avg_price_less%22%3Afalse%2C%22subjects%22%3A%7B%22all%22%3Atrue%7D%2C%22lang_ru%22%3Atrue%2C%22lang_ua%22%3Atrue%2C%22keywords%22%3Afalse%2C%22url%22%3Afalse%2C%22not_contains_link%22%3Afalse%2C%22domains%22%3A%7B%22all%22%3Atrue%7D%2C%22added_days%22%3A%22all%22%2C%22search_type%22%3A%22%22%2C%22quick_filter%22%3A%22%22%2C%22quick_filter_default_sort%22%3A%22%22%7D&anchor_token=e3d6486b38d4ca1860364611a5f5c258&from_ses=1&count_in_page=false",
            CURLOPT_HTTPHEADER => array(
                "Accept: */*",
                "Accept-Encoding: gzip, deflate",
                "Accept-Language: ru-RU,ru;q=0.8,en-US;q=0.5,en;q=0.3",
                "Cache-Control: no-cache",
                "Connection: keep-alive",
                "Content-Length: 1377",
                "Content-Type: application/x-www-form-urlencoded",
                "Referer: https://gogetlinks.net/search_sites.php",
                "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:67.0) Gecko/20100101 Firefox/67.0",
                "X-Requested-With: XMLHttpRequest",
                "cache-control: no-cache,no-cache"
            ),
        ));


        $response = curl_exec($curl);
        $response = mb_convert_encoding($response, "utf-8", "windows-1251");

        //file_put_contents(public_path('sites/gogetlinks/page.html'),$response);


        $err = curl_error($curl);

        curl_close($curl);

        if ($err) {
            Log::info(print_r($err, 1));
            return false;
        } else {
            return $response;
        }
    }

}

function get_string_between($string, $start, $end) {
    $string = ' ' . $string;
    $ini = strpos($string, $start);
    if ($ini == 0)
        return '';
    $ini += strlen($start);
    $len = strpos($string, $end, $ini) - $ini;
    return substr($string, $ini, $len);
}
