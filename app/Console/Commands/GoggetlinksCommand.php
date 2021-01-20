<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Log;
use App\Models\{
    Domains,
    Gogetlinks
};
use Carbon\Carbon;
use Symfony\Component\DomCrawler\Crawler;

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

        if (!$this->checkLogin()) {
            die();
        }

        $log_folder = storage_path('logs/debug/gogetlinks');

        $page = 0;
        $retries = 3;
        $url = "/<a ?.*>(.*)<\/a>/";
        $traffik = '/(.*)<\/td>/';
        $price = '/value="(.*)"><\/label>/';
        $quantity = "/(.*)<\/td>/";
        $added = 0;


        $counter = array(
            'current' => 0,
            'total' => 0,
            'new' => 0,
            'updated' => 0,
        );

        //set 50 sites per page
        //file_put_contents(public_path(env('GOGETLINKS_COOKIE_FILE')),'gogetlinks.net	FALSE	/	FALSE	1602936216	in_page_search_sites	50'.PHP_EOL, FILE_APPEND);

        //Получим количество сайтов
        $data = $this->getData($page);
        file_put_contents(storage_path('gogetlinks.page.html'),$data);

        $crawler = new Crawler($data);

        $counter['total'] = $crawler->filter('body > div > table tr td')->first()->text();
        $counter['total'] = $this->convertToNumber($counter['total']);

        //$counter['total'] = $crawler->filterXPath('//*[@id="link_hint_232330"]');
        $counter['current'] = 0;

        while ($counter['current'] < $counter['total']) {

            //Эту строку нельзя использовать в условии выше, т.к. она иногда отдает пустой ответ и скан заканчивается
            $data = $this->getData($page);

            file_put_contents($log_folder.'/'.$counter['total'].'.html',$data);

            $page_valid = (boolean)stripos($data,'<tbody id="body_table_content">');
            $current_retry = $retries;

            if ($page_valid) {

                $current_page_dom = new Crawler($data);

                //Проверить Убираем зацикливание на последней странице ?

                $rows = $current_page_dom->filter('tr.search_sites_row')->each(function (Crawler $node, $i) {
                    return $node->html();
                });

                $counter['current'] = $counter['current'] + count($rows);

                foreach ($rows as $row) {
                    $data = [];

                    file_put_contents(storage_path('row.dom.html'),$row);

                    $row_dom = new Crawler($row);

                    $data['id'] = $row_dom->filter('input.row-id')->attr('value');
                    $data['traffic'] = $this->convertToNumber($row_dom->filter('td.row_'.$data['id'])->eq(3)->text());
                    $data['domain'] = $row_dom->filter('#link_hint_'.$data['id'])->text();
                    //На гогете 3 цены, некоторых нету - берем максимальную, обычно это статья
                    $prices = $row_dom->filter('body label input[type=hidden]')->each(function (Crawler $node, $i) {
                        return $node->attr('value');
                    });

                    $data['placement_price'] = max($prices);

                       if ($domain = Domains::where('url', $data['domain'])->first()) {
                        $data['domain_id'] = $domain->id;
                    } else {
                        $domain = Domains::insertGetId(['url' => $data['domain']]);
                        $data['domain_id'] = $domain;
                    }

                    if (Gogetlinks::where('domain_id', $data['domain_id'])->first()) {
                        Gogetlinks::where('domain_id', $data['domain_id'])->update([
                            'placement_price' => $data['placement_price'],
                            'traffic' => $data['traffic'],
                            'domain_id' => $data['domain_id'],
                        ]);
                        $counter['updated']++;
                    } else {
                        Gogetlinks::insert([
                            'placement_price' => $data['placement_price'],
                            'traffic' => $data['traffic'],
                            'domain_id' => $data['domain_id'],
                            'updated_at' => date('Y-m-d H:i:s')
                        ]);
                        $counter['new']++;
                        //Добавляем updated_at при создании, чтоб в конце обновления удалить домены у которых updated_at отличается на Х часов от времени обновления
                    }

                    $added++;
                }

                $antiban_pause = mt_rand(30, 50);
                $this->line('Gogetlinks.ru page : ' . $page . ' | Fetched domains : ' . count($rows) . ' | Progress: '.$counter['current'].'/'.$counter['total'].' | Added total : ' . $counter['new'] . ' | Updated total : ' . $counter['updated'] . ' | Sleeping for ' . $antiban_pause . ' seconds');
                sleep($antiban_pause);

                $page++;

            } else {
                //Если страница так и не прогрузилась, то пропускаем ее
                $antiban_pause = mt_rand(30, 50);
                $this->line('Gogetlinks.ru page : ' . $page . ' | Problem fetching page, retrying | Sleeping for ' . $antiban_pause . ' seconds');
                sleep($antiban_pause);
            }


        }

        $this->call('domains:finalize', [
            '--table' => (new Gogetlinks())->getTable(),
            //Гогетлинкс обновляется медленно, поэтому окно обновления в часах ставим больше обычного
            '--hours' => 30
        ]);

    }

    private function checkLogin()
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://gogetlinks.net/profile');
        curl_setopt($ch, CURLOPT_REFERER, env('GOGETLINKS_LOGIN_URL'));
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch,CURLOPT_ENCODING, '');
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_COOKIEJAR, public_path(env('GOGETLINKS_COOKIE_FILE')));
        curl_setopt($ch, CURLOPT_COOKIEFILE, public_path(env('GOGETLINKS_COOKIE_FILE')));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER,array(
            "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/83.0.4103.97 Safari/537.36",
            "Accept:  */*",
            "Accept-Encoding:  gzip, deflate",
            "Accept-Language:  ru-RU,ru;q=0.8,en-US;q=0.5,en;q=0.3",
            "Cache-Control:  no-cache",
            "Connection:  keep-alive",
            "Content-Type: application/x-www-form-urlencoded",
            "Referer:  https://gogetlinks.net",
            "cache-control:  no-cache",
        ));

        $html = curl_exec($ch);

        if (!strpos($html, '<li class="header-authorized__nav-item">')) {
            file_put_contents(public_path('sites/gogetlinks/debug.html'),$html);
            $this->error('Login not successful : saving page to '.url('/sites/gogetlinks/debug.html').PHP_EOL);
            $this->error('Most likely that the cookies has expired'.PHP_EOL);
            return false;
        } else {
            $this->line('Auth successfull');
            return true;
        }

    }

    private function login() {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, env('GOGETLINKS_LOGIN_URL'));
        curl_setopt($ch, CURLOPT_REFERER, env('GOGETLINKS_LOGIN_URL'));
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch,CURLOPT_ENCODING, '');
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_COOKIEJAR, public_path(env('GOGETLINKS_COOKIE_FILE')));
        curl_setopt($ch, CURLOPT_COOKIEFILE, public_path(env('GOGETLINKS_COOKIE_FILE')));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/47.0.2526.106 Safari/537.36");
        curl_setopt($ch, CURLOPT_HTTPHEADER,array(
            "Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.9",
            "Accept-Encoding: gzip, deflate, br",
            "Accept-Language: ru-RU,ru;q=0.9,en-US;q=0.8,en;q=0.7,uk;q=0.6",
            "Cache-Control: no-cache",
            "Connection: keep-alive",
            "Pragma: no-cache",
            "Sec-Fetch-Dest: document",
            "Sec-Fetch-Mode: navigate",
            "Sec-Fetch-Site: none",
            "Sec-Fetch-User: ?1",
            "Upgrade-Insecure-Requests: 1",
            "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/83.0.4103.97 Safari/537.36",
        ));


        $html = curl_exec($ch);

        $postinfo = "g-recaptcha-response="
                ."e_mail=" . env('GOGETLINKS_USERNAME')
                . "&password=" . env('GOGETLINKS_PASSWORD')
                . "&remember=on";

        curl_setopt($ch, CURLOPT_URL, env('GOGETLINKS_LOGIN_URL_POST'));
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch,CURLOPT_ENCODING, '');
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postinfo);

        $html = curl_exec($ch);
        curl_close($ch);

    }

    private function getData($page = 0) {
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
            CURLOPT_COOKIE => 'in_page_search_sites=100',
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

        //file_put_contents(public_path('sites/gogetlinks/page'.$page.'.html'),$response);


        $err = curl_error($curl);

        curl_close($curl);

        if ($err) {
            Log::info(print_r($err, 1));
            return false;
        } else {
            return $response;
        }
    }

    private function convertToNumber($string) : int
    {
        return preg_replace('/[^0-9]/', '', $string);
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
