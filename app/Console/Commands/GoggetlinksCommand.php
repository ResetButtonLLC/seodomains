<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Log;
use App\Models\Domains;

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
        $page = 1;
        $url = "/<a ?.*>(.*)<\/a>/";
        $traffik = '/(.*)<\/td>/';
        $price = '/value="(.*)"><\/label>/';
        while ($data = $this->getData($page)) {
            $data = explode('<tbody id="body_table_content">', $data);
            $row = explode('search_sites_row', $data[1]);
            unset($row[0]);

            foreach ($row as $col) {
                $data = [];
                $values = explode('<td', $col);
                preg_match($url, $values[1], $matches);
                if ($matches) {
                    $data['url'] = utf8_encode($matches[1]);

                    unset($matches);
                }

                preg_match($traffik, $values[4], $matches);
                if ($matches) {
                    $data['traffic'] = preg_replace('/\D/', '', $matches[1]);
                    unset($matches);
                }

                preg_match($price, $values[8], $matches);
                if ($matches) {
                    $data['placement_price'] = $matches[1];
                    unset($matches);
                }

                if ($data && !Domains::where('url', $data['url'])->where('source', 'gogetlinks')->first()) {
                    $data['source'] = 'gogetlinks';
                    Domains::insert($data);
                    unset($data);
                }
            }

            $page++;
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
        dd(777);
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
            CURLOPT_URL => "https://gogetlinks.net/search_sites.php?action=search&additional_action=change_count_in_page",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_POSTFIELDS => "compaing_id_list=574472&page=" . $page . "&order_by=&order_direction=desc&condition=%7B%22type_search_engine%22%3A2%2C%22is_link%22%3Atrue%2C%22is_post%22%3Atrue%2C%22is_paper%22%3Atrue%2C%22tic_from%22%3Afalse%2C%22tic_to%22%3Afalse%2C%22sqi_from%22%3Afalse%2C%22sqi_to%22%3Afalse%2C%22da_from%22%3Afalse%2C%22da_to%22%3Afalse%2C%22trust_flow%22%3Afalse%2C%22ignore_sape_links%22%3Atrue%2C%22only_exclusive%22%3Afalse%2C%22in_any_catalog%22%3Afalse%2C%22in_yandex_catalog%22%3Afalse%2C%22in_news_aggregator%22%3Afalse%2C%22reviewing_long%22%3Afalse%2C%22reviewing_long_na%22%3Atrue%2C%22indexation%22%3Afalse%2C%22indexation_na%22%3Atrue%2C%22from_white_list%22%3A%5B%5D%2C%22hide_black_list%22%3Atrue%2C%22ignore_rejected_sites%22%3Atrue%2C%22backreferencing%22%3Afalse%2C%22traffic_host%22%3Afalse%2C%22traffic_with_no_data%22%3Atrue%2C%22price_type%22%3A1%2C%22price_paper%22%3Afalse%2C%22price_post%22%3Afalse%2C%22price_link%22%3Afalse%2C%22avg_price_less%22%3Afalse%2C%22subjects%22%3A%7B%224%22%3Atrue%2C%225%22%3Atrue%2C%227%22%3Atrue%2C%2210%22%3Atrue%2C%2215%22%3Atrue%2C%2237%22%3Atrue%2C%2238%22%3Atrue%2C%2239%22%3Atrue%2C%2240%22%3Atrue%2C%2241%22%3Atrue%2C%2242%22%3Atrue%2C%2243%22%3Atrue%2C%2244%22%3Atrue%2C%2245%22%3Atrue%2C%2246%22%3Atrue%2C%2247%22%3Atrue%2C%2248%22%3Atrue%2C%2249%22%3Atrue%2C%2259%22%3Atrue%2C%2260%22%3Atrue%2C%2261%22%3Atrue%2C%2262%22%3Atrue%2C%2288%22%3Atrue%2C%2289%22%3Atrue%2C%2290%22%3Atrue%2C%2291%22%3Atrue%2C%2292%22%3Atrue%7D%2C%22lang_ru%22%3Atrue%2C%22lang_ua%22%3Atrue%2C%22keywords%22%3Afalse%2C%22url%22%3Afalse%2C%22not_contains_link%22%3Afalse%2C%22domains%22%3A%7B%22all%22%3Atrue%7D%2C%22added_days%22%3A%22all%22%2C%22search_type%22%3A%22%22%2C%22quick_filter%22%3A%22%22%2C%22quick_filter_default_sort%22%3A%22%22%7D&anchor_token=af8c574fdab2afb242c40718ee63f38b&from_ses=1&count_in_page=false",
            CURLOPT_HTTPHEADER => array(
                "Accept: */*",
                "Accept-Encoding: gzip, deflate",
                "Accept-Language: ru-RU,ru;q=0.8,en-US;q=0.5,en;q=0.3",
                "Cache-Control: no-cache",
                "Connection: keep-alive",
                "Content-Length: 1845",
                "Content-Type: application/x-www-form-urlencoded; charset=UTF-8",
                "Cookie: PHPSESSID=0b4455e982679013814a8c59b1ba3477; lang=ru; select_menu=1; _jsuid=1232520130; _a0=16c74fc91e46fa4287bc6a533e203d27; type_user=buy; hash=ed82da285a6dea6d3ee73a4990a5880e; rem_acc=%7B%22y.kozlov%40promodo.ru%22%3A%22ed82da285a6dea6d3ee73a4990a5880e%22%7D; in_page_my_comps=20; selected_submenu=1; _ll=promodo_bn; e_mail=y.kozlov%40promodo.ru; _first_pageview=1; no_tracky_100898786=1,PHPSESSID=0b4455e982679013814a8c59b1ba3477; lang=ru; select_menu=1; _jsuid=1232520130; _a0=16c74fc91e46fa4287bc6a533e203d27; type_user=buy; hash=ed82da285a6dea6d3ee73a4990a5880e; rem_acc=%7B%22y.kozlov%40promodo.ru%22%3A%22ed82da285a6dea6d3ee73a4990a5880e%22%7D; in_page_my_comps=20; selected_submenu=1; _ll=promodo_bn; e_mail=y.kozlov%40promodo.ru; _first_pageview=1; no_tracky_100898786=1; PHPSESSID=904fb284d16bc888367e26c7b3982b80; lang=ru; select_menu=1",
                "Host: gogetlinks.net",
                "Postman-Token: 72a4d9ad-49ca-485b-9a64-f4c335019ee3,79d59a62-587e-466c-beb6-df775eec31e9",
                "Referer: https://gogetlinks.net/search_sites.php",
                "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:67.0) Gecko/20100101 Firefox/67.0",
                "X-Requested-With: XMLHttpRequest",
                "cache-control: no-cache"
            ),
        ));

        $response = curl_exec($curl);
        $err = curl_error($curl);

        curl_close($curl);

        if ($err) {
            return false;
        } else {
            return $response;
        }
    }

}
