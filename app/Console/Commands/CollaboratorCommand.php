<?php

namespace App\Console\Commands;

use App\Helpers\DomainsHelper;
use App\Models\Domains;
use App\Models\Collaborator;
use Symfony\Component\DomCrawler\Crawler;

class CollaboratorCommand extends ParserCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'domains:collaborator';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Load domains from collaborator.pro';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $this->initLog('collaborator');

        if ($this->checkLogin()) {
            //get count pages
            $result = $this->makeRequest();
            $result_doom = new Crawler($result);
            $total = $result_doom->filter('.filter-panel ul li b')->text();
            $pages = $result_doom->filter('.page-item_next')->previousAll()->filter('a')->attr('data-page');

            //set counter
            $counter = array(
                'current' => 0,
                'total' => preg_replace('/[^0-9]/', '', $total),
                'new' => 0,
                'updated' => 0,
            );

            //get pages
            for ($page = 1; $page <= intval($pages); $page++) {
                //get all domains
                $domains = Domains::all('id', 'url');

                //set sleep time
                $sleep = mt_rand(30, 50);

                //get page data
                $page_result = $this->makeRequest($page);
                $page_result_doom = new Crawler($page_result);

                //get rows from page
                $rows = $page_result_doom->filter('table.c-table tbody tr')->each(function ($content) {
                    return $content->html();
                });

                //each rows
                if (count($rows)) {
                    foreach ($rows as $row) {
                        $row_doom = new Crawler($row);
                        $data = [];

                        if ($row_doom->count()) {
                            $data['site_id'] = intval($row_doom->filter('.grid-group-checkbox')->attr('value'));
                            $data['url'] = $row_doom->filter('.link')->text();
                            if ($row_doom->filter('.creator-price_catalog')->count() > 0) {
                                $data['price'] = DomainsHelper::getPriceFromString($row_doom->filter('.creator-price_catalog')->attr('data-publication'));
                            } else {
                                $data['price'] = 0;
                            }
                            
                            $data['traffic'] = $row_doom->filter('ul.list-traffic li')->first()->text();
                            $theme = $row_doom->filter('.c-t-theme__tags .tag')->each(function ($content) {
                                return $content->text();
                            });
                            $data['theme'] = implode("; ", $theme);

                            if ($data && preg_match('/([a-zA-Z0-9\-_]+\.)?[a-zA-Z0-9\-_]+\.[a-zA-Z]{2,5}/', $data['url'])) {
                                //find domain id or create
                                $data['domain_id'] = DomainsHelper::getIdByUrl($domains, $data['url']);
                                if (!$data['domain_id']) {
                                    $data['domain_id'] = Domains::insertGetId(['url' => $data['url'], 'created_at' => date('Y-m-d H:i:s')]);
                                }

                                //update or create new collaborator
                                $collaborator = Collaborator::updateOrCreate(
                                    ['domain_id' => $data['domain_id']],
                                    ['site_id' => $data['site_id'], 'url' => $data['url'], 'price' => intval($data['price']), 'theme' => $data['theme'], 'traffic' => $data['traffic']]
                                );

                                //update counter
                                if($collaborator->created_at == $collaborator->updated_at) {
                                    $counter['new']++;
                                } else {
                                    $counter['updated']++;
                                }

                                $counter['current']++;
                            }
                        }
                    }

                    $this->writeLog('collaborator.pro page : ' . $page . ' | Fetched domains : ' . count($rows) . ' | Progress ' . $counter['current'] . '/' . $counter['total'] . ' | Added total : ' . $counter['new'] . ' | Updated total : ' . $counter['updated'] . ' | Sleeping for ' . $sleep . ' seconds');
                    sleep($sleep);
                }
            }

            $this->call('domains:finalize', [
                '--table' => 'collaborators'
            ]);

        }
    }

    private function checkLogin()
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://collaborator.pro/dashboard/deals');
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch,CURLOPT_ENCODING, '');
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_COOKIEJAR, $this->getCookie());
        curl_setopt($ch, CURLOPT_COOKIEFILE, $this->getCookie());
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);

        $html = curl_exec($ch);

        if (!strpos($html, 'Пополнить баланс')) {
            $this->writeHtmlLogFile('auth.html', $html);
            $this->writeLog('Auth fail');
            return false;
        } else {
            $this->writeLog('Auth successfull');
            return true;
        }
    }

    private function makeRequest($page = 1)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://collaborator.pro/catalog/creator/article?page=' . $page . '&per-page=100');
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch,CURLOPT_ENCODING, '');
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_COOKIEJAR, $this->getCookie());
        curl_setopt($ch, CURLOPT_COOKIEFILE, $this->getCookie());
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);

        $html = curl_exec($ch);

        $this->writeHtmlLogFile('page-'  . $page . '.html', $html);

        return $html;
    }
}
