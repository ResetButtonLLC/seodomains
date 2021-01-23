<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\{
    Domains,
    Prnews
};
use Symfony\Component\DomCrawler\Crawler;
use App\Helpers\DomainsHelper;

class PrnewsCommand extends Command {

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'domains:prnews';

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

    private $logfolder;

    public function __construct() {
        parent::__construct();
        $this->logfolder = storage_path('logs/debug/prnews/');
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle() {

        $this->line('prnews.io');
        $page = 1;

        $counter = array(
            'current' => 0,
            'total' => 0,
            'new' => 0,
            'updated' => 0,
        );

        //Курс гривны к рублю
        $exchange_rates = json_decode(file_get_contents('https://api.privatbank.ua/p24api/pubinfo?exchange&json&coursid=11'));
        foreach ($exchange_rates as $exchange_rate) {
            if ($exchange_rate->ccy == "RUR") {
                $uah_to_rur = $exchange_rate->buy;
            }
        }

        //Получаем все домены
        $db_domains = Domains::all('id','url');

        while ($counter['current'] <= $counter['total']) {

            $data = $this->getData($page);

            if (!$this->checkLoggedIn($data)) {
                die();
            }

            if ($page == 1) {
                //Количество сайтов на площадке
                $dom = new Crawler($data);
                $counter['total'] =  $dom->filter('div.cards-meta_counter')->text();
                $counter['total'] = preg_replace('/[^0-9]/', '',  $counter['total']);
            }


            $dom = new Crawler($data);
            $sites = $dom->filter('div.js__data-platform-click')->each(function ($content, $i) {
                 return  $content->html();
            });

            foreach ($sites as $site) {

                $row_dom = new Crawler($site);

                //file_put_contents($log_folder.'/card.html',$row_dom->html());
                $data = [];

                $data['url'] = utf8_encode($row_dom->filter('div.card_url')->first()->text());
                $data['url'] = preg_replace('/^www\./','',$data['url']);

                $data['price'] = $row_dom->filter('div.card_price')->first()->text();
                $data['price'] = preg_replace('/[^0-9,]/','',$data['price']);
                $data['price'] = str_ireplace(',','.',$data['price']);
                $data['price'] = (int)round($data['price']/$uah_to_rur,0);

                $data['audience'] = trim($row_dom->filter('div.card_audience')->first()->text());

                if ($data) {

                    $data['domain_id'] = DomainsHelper::getIdByUrl($db_domains,$data['url']);
                    if (!$data['domain_id']) {
                        $data['domain_id'] = Domains::insertGetId(['url' => $data['url'], 'created_at' => date('Y-m-d H:i:s')]);
                    }

                    $prnews = Prnews::updateOrCreate(
                        ['domain_id' => $data['domain_id']],
                        ['price' => $data['price'], 'audience' => $data['audience']]
                    );

                    if($prnews->created_at == $prnews->updated_at) {
                        $counter['new']++;
                    } else {
                        $counter['updated']++;
                    }

                    $counter['current']++;

                }

                //dd($data);
            }
            $antiban_pause = mt_rand(0, 0);
            $this->line('prnews.io page : ' . $page . ' | Fetched domains : ' . count($sites) . ' | Progress '.$counter['current'].'/'.$counter['total'].' | Added total : ' . $counter['new'] . ' | Updated total : ' . $counter['updated'].' | Sleeping for ' . $antiban_pause . ' seconds');
            sleep($antiban_pause);

            $page++;
        }

        $this->call('domains:finalize', [
            '--table' => 'prnews',
            '--hours' => 3
        ]);
        //dd($this->getData());

    }

    private function getData($page)
    {
        $url = $page == 1 ? 'https://prnews.io/ru/sites/perpage/96/' : 'https://prnews.io/ru/sites/page/'. $page .'/perpage/96/';

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_COOKIEJAR, storage_path('/app/cookies/cookie-file-prnews-jar.txt'));
        curl_setopt($ch, CURLOPT_COOKIEFILE, storage_path('/app/cookies/cookie-file-prnews.txt'));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/87.0.4280.141 Safari/537.36");
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            "Connection: keep-alive",
            "accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3",
            "accept-language: ru-RU,ru;q=0.9,en-US;q=0.8,en;q=0.7,uk;q=0.6",
            "cache-control: no-cache,no-cache",
        ));

        $page_valid = false;

        while (!$page_valid) {
            $curl_response = curl_exec($ch);
            $page_valid = stripos($curl_response,'<div class="cards-meta">');
            if (!$page_valid) {
                $antiban_pause = mt_rand(30, 50);
                $this->line('Prnews.ru | Get empty responce | sleeping for '.$antiban_pause.' seconds');
                sleep($antiban_pause);
            }
        }

        file_put_contents($this->logfolder.'/'.$page.'.html',$curl_response);

        curl_close($ch);

        return $curl_response;
    }

    private function isLogged() : string
    {
        $ch = curl_init('https://prnews.io/account/');
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_COOKIEJAR, storage_path('/app/cookies/cookie-file-prnews.txt'));
        curl_setopt($ch, CURLOPT_COOKIEFILE, storage_path('/app/cookies/cookie-file-prnews.txt'));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/47.0.2526.106 Safari/537.36");
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            "Connection: keep-alive",
            "accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3",
            "accept-language: ru-RU,ru;q=0.9,en-US;q=0.8,en;q=0.7,uk;q=0.6",
            "cache-control: no-cache,no-cache",
        ));

        $html = curl_exec($ch);

        if (!strpos($html, 'https://prnews.io/api/logout')) {
            $this->error('Prnews : Login not successful : Most likely that the cookies has expired'.PHP_EOL);
            file_put_contents($this->logfolder.'/login.html',$html);
            return false;
        } else {
            $this->line('Auth successfull');
            return $html;
        }

    }

    private function checkLoggedIn($page_content) : bool
    {
        if (strpos($page_content, 'link-signup')) {
            $this->error('Prnews : Login not successful : Most likely that the cookies has expired'.PHP_EOL);
            file_put_contents($this->logfolder.'/bad_page.html',$page_content);
            return false;
        } else {
            return true;
        }
    }



}
