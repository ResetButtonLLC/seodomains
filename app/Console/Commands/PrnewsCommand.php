<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\{
    Domains,
    Prnews
};
use Symfony\Component\DomCrawler\Crawler;

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
    public function __construct() {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle() {

        $log_folder = storage_path('logs/debug/prnews');

        $this->line('prnews.io parsing');
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



        while ($data = $this->getData($page)) {

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
                    if ($domain = Domains::where('url', $data['url'])->first()) {
                        $data['domain_id'] = $domain->id;
                    } else {
                        $domain = Domains::insertGetId(['url' => $data['url'], 'created_at' => date('Y-m-d H:i:s')]);
                        $data['domain_id'] = $domain;
                    }

                    if (Prnews::where('domain_id', $data['domain_id'])->first()) {
                        $data['updated_at'] = date('Y-m-d H:i:s');
                        Prnews::where('domain_id', $data['domain_id'])->update($data);
                        $counter['updated']++;
                    } else {
                        $data['created_at'] = date('Y-m-d H:i:s');
                        Prnews::insert($data);
                        $counter['new']++;
                    }
                }

                //dd($data);
            }

            $this->line('prnews.io page : ' . $page . ' | Fetched domains : ' . count($sites) . ' |  Added total : ' . $counter['new'] . ' | Updated total : ' . $counter['updated']);

            $page++;
        }

        $this->call('domains:finalize', [
            '--table' => 'prnews',
            '--hours' => 3
        ]);
        //dd($this->getData());

    }

    private function getData($page) {
        $url = $page == 1 ? 'https://prnews.io/sites/perpage/96/' : 'https://prnews.io/sites/page/'. $page .'/perpage/96/';

        $ch = curl_init($url);
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

        $curl_response = curl_exec($ch);

        //file_put_contents(storage_path().'/prnews.html',$curl_response);

        curl_close($ch);

        return $curl_response;
    }



}
