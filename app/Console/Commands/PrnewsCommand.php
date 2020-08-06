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
        $this->line('prnews.io parsing');
        $page = 1;

        $counter = array(
            'current' => 0,
            'total' => 0,
            'new' => 0,
            'updated' => 0,
        );

        $url = '/<div class="card_url">(.*)<\/div>/';
        $price = '/<div class="card_price">(.*)<\/div>/';
        $audience = '/<div class="card_audience">(.*)<\/div>/';

        while ($data = $this->getData($page)) {
            $dom = new Crawler($data);
            $sites = $dom->filter('div.js__data-platform-click')->each(function ($content, $i) {
                    return  $content->html();
            });;

            foreach ($sites as $site) {
                $data = [];
                preg_match($url, $site, $matches);
                if ($matches) {
                    $data['url'] = utf8_encode($matches[1]);
                    unset($matches);
                    $data['url'] = preg_replace('/^www\./','',$data['url']);
                }

                preg_match($price, $site, $matches);
                if ($matches) {
                    $data['price'] = trim(preg_replace('[^0-9\.,]', '', $matches[1]));
                    unset($matches);
                    $data['price'] = preg_replace('/[^0-9\.,]/','',$data['price']);
                }

                preg_match($audience, $site, $matches);
                if ($matches) {
                    $data['audience'] = utf8_encode($matches[1]);
                    unset($matches);
                }
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
            }

            $this->line('prnews.io page : ' . $page . ' | Fetched domains : ' . count($sites) . ' |  Added total : ' . $counter['new'] . ' | Updated total : ' . $counter['updated']);

            $page++;
        }

        $this->call('domains:finalize', [
            '--table' => 'prnews',
            //Гогетлинкс обновляется медленно, поэтому окно обновления в часах ставим больше обычного
            '--hours' => 3
        ]);
        //dd($this->getData());

    }

    private function getData($page) {
        $url = $page == 1 ? 'https://prnews.io/sites/perpage/72/' : 'https://prnews.io/sites/page/'. $page .'/perpage/72/';

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

        $curl_response = curl_exec($ch);
        curl_close($ch);

        return $curl_response;
    }



}
