<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\{Domains, Gogetlinks};
use Carbon\Carbon;
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

            $this->writeHtmlLogFile($counter['total'] . '.html', $data);

            $page_valid = (boolean)stripos($data,'<table class="table" id="table_content">');

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
            return false;
        } else {
            $this->writeLog('Auth successfull');
            return true;
        }

    }



    private function getData($page = 0) {
        if ($page > 0) {
            $params = 'action=search&additional_action=change_count_in_page&page=' . $page;
        } else {
            $params = 'action=search';
        }

        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => 'https://gogetlinks.net/searchSites',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_COOKIEJAR => $this->getCookie(),
            CURLOPT_COOKIEFILE => $this->getCookie(),
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => array('compaing_id_list' => '583393','page' => $page,'condition' => '{"type_search_engine":2,"is_link":true,"is_post":true,"is_paper":true,"tic_from":false,"tic_to":false,"sqi_from":false,"sqi_to":false,"da_from":false,"da_to":false,"rank_from":false,"rank_to":false,"trust_flow":false,"ignore_sape_links":true,"only_exclusive":false,"in_any_catalog":false,"in_yandex_catalog":false,"in_news_aggregator":false,"reviewing_long":5,"reviewing_long_na":true,"indexation":false,"indexation_na":true,"from_white_list":[],"hide_black_list":true,"ignore_rejected_sites":true,"backreferencing":false,"traffic_host":false,"traffic_with_no_data":true,"price_type":1,"price_paper":false,"price_post":false,"price_link":false,"avg_price_less":false,"subjects":{"1":true,"2":true,"3":true,"4":true,"5":true,"6":true,"7":true,"8":true,"9":true,"10":true,"11":true,"12":true,"13":true,"14":true,"15":true,"16":true,"17":true,"18":true,"19":true,"20":true,"21":true,"22":true,"23":true,"24":true,"25":true,"26":true,"27":true,"28":true,"29":true,"30":true,"31":true,"32":true,"33":true,"34":true,"35":true,"36":true,"37":true,"38":true,"39":true,"40":true,"41":true,"42":true,"43":true,"44":true,"45":true,"46":true,"47":true,"48":true,"49":true,"50":true,"51":true,"52":true,"53":true,"54":true,"55":true,"56":true,"57":true,"58":true,"59":true,"60":true,"61":true,"62":true,"63":true,"64":true,"65":true,"66":true,"67":true,"68":true,"69":true,"70":true,"71":true,"72":true,"73":true,"74":true,"75":true,"76":true,"77":true,"78":true,"79":true,"80":true,"81":true,"82":true,"84":true,"85":true,"86":true,"87":true,"88":true,"89":true,"90":true,"91":true,"92":true,"93":true,"94":true,"95":true,"96":true,"97":true,"98":true,"99":true,"100":true,"101":true,"102":true,"103":true,"104":true,"105":true,"106":true,"107":true,"108":true,"109":true,"110":true,"111":true,"112":true,"113":true,"114":true,"115":true,"116":true,"117":true,"118":true,"119":true,"120":true,"121":true,"122":true,"123":true,"124":true,"125":true,"126":true},"lang_ru":true,"lang_ua":true,"keywords":false,"url":false,"not_contains_link":false,"domains":{"all":true},"added_days":"all","search_type":"","quick_filter":"","quick_filter_default_sort":""}'),
            CURLOPT_HTTPHEADER => array(
                "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/83.0.4103.97 Safari/537.36",
                "Accept:  */*",
                "Accept-Encoding:  gzip, deflate",
                "Accept-Language:  ru-RU,ru;q=0.8,en-US;q=0.5,en;q=0.3",
                "Cache-Control:  no-cache",
                "Connection:  keep-alive",
                "Content-Type: application/x-www-form-urlencoded",
                "Referer:  https://gogetlinks.net",
                "cache-control:  no-cache",
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
