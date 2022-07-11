<?php

namespace App\Console\Commands\LinkExchange\Obsolete;

use App\Models\{Domain, Gogetlinks};
use Symfony\Component\DomCrawler\Crawler;

class GoggetlinksCommand extends ParserCommand {

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
        $this->initLog('gogetlinks');

        if (!$this->checkLogin()) {
            die();
        }

        $log_folder = storage_path('parserlogs/gogetlinks');

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

        if ($data === null) {
            $this->writeLog('Ошибка получения кол-ва сайтов');
            return false;
        }

        $this->writeHtmlLogFile('gogetlinks.page.html', $data);

        $crawler = new Crawler($data);

        //dd($crawler->filter('body > div > table tr td')->first());

        $counter['total'] = $crawler->filter('.table__before strong')->first()->text();

        $counter['total'] = $this->convertToNumber($counter['total']);



        //dd($counter['total']);


        //$counter['total'] = $crawler->filterXPath('//*[@id="link_hint_232330"]');
        $counter['current'] = 0;

        while ($counter['current'] < $counter['total']) {

            //Эту строку нельзя использовать в условии выше, т.к. она иногда отдает пустой ответ и скан заканчивается
            $data = $this->getData($page);

            if ($data === null) {
                $this->writeLog('Пропуск страницы ' . $page);
                $page++;
                continue;
            }

            $this->writeHtmlLogFile($counter['total'] . '.html', $data);

            $page_valid = (boolean)stripos($data,'<table class="table table_compact" id="table_content">');

            $current_retry = $retries;

            if ($page_valid) {

                $current_page_dom = new Crawler($data);

                //Проверить Убираем зацикливание на последней странице ?

                $rows = $current_page_dom->filter('.js-search-sites-tbody tr')->each(function (Crawler $node, $i) {
                    return $node->html();
                });

                $counter['current'] = $counter['current'] + count($rows);

                foreach ($rows as $row) {
                    $data = [];

                    $this->writeHtmlLogFile('row.dom.html', $row);

                    $row_dom = new Crawler($row);

                    $data['id'] = $row_dom->filter('input.row-id')->attr('value');
                    $data['traffic'] = $this->convertToNumber($row_dom->filter('.tablet__td')->eq(0)->text());
                    $data['domain'] = $row_dom->filter('#link_hint_'.$data['id'])->text();
                    //На гогете 3 цены, некоторых нету - берем максимальную, обычно это статья
                    $prices = [];
                    if ($row_dom->filter("input[type=hidden]#h0_" . $data['id'])->count()) $prices[0] = $row_dom->filter("input[type=hidden]#h0_" . $data['id'])->attr('value');
                    if ($row_dom->filter("input[type=hidden]#h1_" . $data['id'])->count()) $prices[1] = $row_dom->filter("input[type=hidden]#h1_" . $data['id'])->attr('value');
                    if ($row_dom->filter("input[type=hidden]#h2_" . $data['id'])->count()) $prices[2] = $row_dom->filter("input[type=hidden]#h2_" . $data['id'])->attr('value');

                    $data['placement_price'] = max($prices);

                    if ($domain = Domain::where('url', $data['domain'])->first()) {
                        $data['domain_id'] = $domain->id;
                    } else {
                        $domain = Domain::insertGetId(['url' => $data['domain']]);
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
                $this->writeLog('Gogetlinks.ru page : ' . $page . ' | Fetched domains : ' . count($rows) . ' | Progress: '.$counter['current'].'/'.$counter['total'].' | Added total : ' . $counter['new'] . ' | Updated total : ' . $counter['updated'] . ' | Sleeping for ' . $antiban_pause . ' seconds');
                sleep($antiban_pause);

                $page++;

            } else {
                //Если страница так и не прогрузилась, то пропускаем ее
                $antiban_pause = mt_rand(30, 50);
                $this->writeLog('Gogetlinks.ru page : ' . $page . ' | Problem fetching page, retrying | Sleeping for ' . $antiban_pause . ' seconds');
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
        curl_setopt($ch, CURLOPT_COOKIEJAR, $this->getCookie());
        curl_setopt($ch, CURLOPT_COOKIEFILE, $this->getCookie());
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
            $this->writeHtmlLogFile('debug.html', $html);
            $this->writeLog('Login not successful : saving page to ' . url('/sites/gogetlinks/debug.html'));
            $this->writeLog('Most likely that the cookies has expired');
            $this->sendErrorNotification('auth error most likely that the cookies has expired');
            return false;
        } else {
            $this->writeLog('Auth successfull');
            return true;
        }

    }



    private function getData($page = 0) {
//        if ($page > 0) {
//            $params = 'action=search&additional_action=change_count_in_page&page=' . $page;
//        } else {
//            $params = 'action=search';
//        }

        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => 'https://gogetlinks.net/searchSites/table',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_COOKIEJAR => $this->getCookie(),
            CURLOPT_COOKIEFILE => $this->getCookie(),
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS =>'compaing_id_list%5B%5D=583393&page=' . $page . '&condition=%7B%22type_search_engine%22%3A2%2C%22is_link%22%3Atrue%2C%22is_post%22%3Atrue%2C%22is_paper%22%3Atrue%2C%22tic_from%22%3Afalse%2C%22tic_to%22%3Afalse%2C%22sqi_from%22%3Afalse%2C%22sqi_to%22%3Afalse%2C%22da_from%22%3Afalse%2C%22da_to%22%3Afalse%2C%22rank_from%22%3Afalse%2C%22rank_to%22%3Afalse%2C%22trust_flow%22%3Afalse%2C%22ignore_sape_links%22%3Atrue%2C%22only_exclusive%22%3Afalse%2C%22in_any_catalog%22%3Afalse%2C%22in_yandex_catalog%22%3Afalse%2C%22in_news_aggregator%22%3Afalse%2C%22reviewing_long%22%3A5%2C%22reviewing_long_na%22%3Atrue%2C%22indexation%22%3Afalse%2C%22indexation_na%22%3Atrue%2C%22from_white_list%22%3A%5B%5D%2C%22hide_black_list%22%3Atrue%2C%22ignore_rejected_sites%22%3Atrue%2C%22backreferencing%22%3Afalse%2C%22traffic_host%22%3Afalse%2C%22traffic_with_no_data%22%3Atrue%2C%22price_type%22%3A1%2C%22price_paper%22%3Afalse%2C%22price_post%22%3Afalse%2C%22price_link%22%3Afalse%2C%22avg_price_less%22%3Afalse%2C%22subjects%22%3A%7B%221%22%3Atrue%2C%222%22%3Atrue%2C%223%22%3Atrue%2C%224%22%3Atrue%2C%225%22%3Atrue%2C%226%22%3Atrue%2C%227%22%3Atrue%2C%228%22%3Atrue%2C%229%22%3Atrue%2C%2210%22%3Atrue%2C%2211%22%3Atrue%2C%2212%22%3Atrue%2C%2213%22%3Atrue%2C%2214%22%3Atrue%2C%2215%22%3Atrue%2C%2216%22%3Atrue%2C%2217%22%3Atrue%2C%2218%22%3Atrue%2C%2219%22%3Atrue%2C%2220%22%3Atrue%2C%2221%22%3Atrue%2C%2222%22%3Atrue%2C%2223%22%3Atrue%2C%2224%22%3Atrue%2C%2225%22%3Atrue%2C%2226%22%3Atrue%2C%2227%22%3Atrue%2C%2228%22%3Atrue%2C%2229%22%3Atrue%2C%2230%22%3Atrue%2C%2231%22%3Atrue%2C%2232%22%3Atrue%2C%2233%22%3Atrue%2C%2234%22%3Atrue%2C%2235%22%3Atrue%2C%2236%22%3Atrue%2C%2237%22%3Atrue%2C%2238%22%3Atrue%2C%2239%22%3Atrue%2C%2240%22%3Atrue%2C%2241%22%3Atrue%2C%2242%22%3Atrue%2C%2243%22%3Atrue%2C%2244%22%3Atrue%2C%2245%22%3Atrue%2C%2246%22%3Atrue%2C%2247%22%3Atrue%2C%2248%22%3Atrue%2C%2249%22%3Atrue%2C%2250%22%3Atrue%2C%2251%22%3Atrue%2C%2252%22%3Atrue%2C%2253%22%3Atrue%2C%2254%22%3Atrue%2C%2255%22%3Atrue%2C%2256%22%3Atrue%2C%2257%22%3Atrue%2C%2258%22%3Atrue%2C%2259%22%3Atrue%2C%2260%22%3Atrue%2C%2261%22%3Atrue%2C%2262%22%3Atrue%2C%2263%22%3Atrue%2C%2264%22%3Atrue%2C%2265%22%3Atrue%2C%2266%22%3Atrue%2C%2267%22%3Atrue%2C%2268%22%3Atrue%2C%2269%22%3Atrue%2C%2270%22%3Atrue%2C%2271%22%3Atrue%2C%2272%22%3Atrue%2C%2273%22%3Atrue%2C%2274%22%3Atrue%2C%2275%22%3Atrue%2C%2276%22%3Atrue%2C%2277%22%3Atrue%2C%2278%22%3Atrue%2C%2279%22%3Atrue%2C%2280%22%3Atrue%2C%2281%22%3Atrue%2C%2282%22%3Atrue%2C%2284%22%3Atrue%2C%2285%22%3Atrue%2C%2286%22%3Atrue%2C%2287%22%3Atrue%2C%2288%22%3Atrue%2C%2289%22%3Atrue%2C%2290%22%3Atrue%2C%2291%22%3Atrue%2C%2292%22%3Atrue%2C%2293%22%3Atrue%2C%2294%22%3Atrue%2C%2295%22%3Atrue%2C%2296%22%3Atrue%2C%2297%22%3Atrue%2C%2298%22%3Atrue%2C%2299%22%3Atrue%2C%22100%22%3Atrue%2C%22101%22%3Atrue%2C%22102%22%3Atrue%2C%22103%22%3Atrue%2C%22104%22%3Atrue%2C%22105%22%3Atrue%2C%22106%22%3Atrue%2C%22107%22%3Atrue%2C%22108%22%3Atrue%2C%22109%22%3Atrue%2C%22110%22%3Atrue%2C%22111%22%3Atrue%2C%22112%22%3Atrue%2C%22113%22%3Atrue%2C%22114%22%3Atrue%2C%22115%22%3Atrue%2C%22116%22%3Atrue%2C%22117%22%3Atrue%2C%22118%22%3Atrue%2C%22119%22%3Atrue%2C%22120%22%3Atrue%2C%22121%22%3Atrue%2C%22122%22%3Atrue%2C%22123%22%3Atrue%2C%22124%22%3Atrue%2C%22125%22%3Atrue%2C%22126%22%3Atrue%7D%2C%22lang_ru%22%3Atrue%2C%22lang_ua%22%3Atrue%2C%22keywords%22%3Afalse%2C%22url%22%3Afalse%2C%22not_contains_link%22%3Afalse%2C%22domains%22%3A%7B%22all%22%3Atrue%7D%2C%22added_days%22%3A%22all%22%2C%22search_type%22%3A%22%22%2C%22quick_filter%22%3A%22%22%2C%22quick_filter_default_sort%22%3A%22%22%2C%22order_by%22%3A%22%22%2C%22order_direction%22%3A%22desc%22%7D&count_in_page=100&additional_action=change_count_in_page&order_by=&order_direction=desc&anchor_token=2ccabd8b98e0d0bc32f17325953e943b&from_ses=1',
            CURLOPT_HTTPHEADER => array(
                'Connection: keep-alive',
                'Pragma: no-cache',
                'Cache-Control: no-cache',
                'sec-ch-ua: " Not;A Brand";v="99", "Google Chrome";v="91", "Chromium";v="91"',
                'Accept: application/json, text/javascript, */*; q=0.01',
                'X-Requested-With: XMLHttpRequest',
                'sec-ch-ua-mobile: ?0',
                'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36',
                'Content-Type: application/x-www-form-urlencoded; charset=UTF-8',
                'Origin: https://gogetlinks.net',
                'Sec-Fetch-Site: same-origin',
                'Sec-Fetch-Mode: cors',
                'Sec-Fetch-Dest: empty',
                'Referer: https://gogetlinks.net/searchSites',
                'Accept-Language: ru,uk;q=0.9,ru-RU;q=0.8,en-US;q=0.7,en;q=0.6',
            ),
        ));

        $response = curl_exec($curl);

        $response = json_decode($response, true);

        $response = mb_convert_encoding($response, "utf-8", "windows-1251");
        //file_put_contents(public_path('sites/gogetlinks/page'.$page.'.html'),$response);


        $err = curl_error($curl);

        curl_close($curl);

        if ($err) {
            return false;
        } else {
            return data_get($response, 'data');
        }
    }

    private function convertToNumber($string) : int
    {
        return preg_replace('/[^0-9]/', '', $string);
    }


}
