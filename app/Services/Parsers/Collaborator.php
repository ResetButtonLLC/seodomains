<?php

namespace App\Services\Parsers;

use App\Dto\CollaboratorDomain;
use App\Dto\ParserProgressCounter;
use App\Models\Domain;
use App\Exceptions\ParserException;
use App\Helpers\DomainsHelper;
use Carbon\Carbon;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Http;
use Illuminate\Contracts\Filesystem\Filesystem;
use Psr\Log\LoggerInterface;
use Symfony\Component\DomCrawler\Crawler;

class Collaborator
{

    const LOGGED_IN_NEEDLE = '/images/icons/logout.svg';
    const NEXT_PAGE_NEEDLE = '<li class="page-item_next"><a class="page-link"';
    protected string $parserName;
    protected LoggerInterface $logChannel;
    protected Filesystem $storage;
    protected PendingRequest $httpClient;
    protected ParserProgressCounter $counter;

    public function __construct()
    {
        $classParts = explode('\\', __CLASS__);
        $this->parserName = strtolower(array_pop($classParts));

        $this->logChannel = Log::build([
            'driver' => 'daily',
            'path' => storage_path('logs/parsers/'.$this->parserName),
        ]);

        Log::stack(['stderr', $this->logChannel])->info('Fire up parser '.$this->parserName);

        $this->storage = Storage::build([
            'driver' => 'local',
            'root' => storage_path('logs/parsers/'.$this->parserName.'/pages'),
        ]);

       $this->setupHttpClient();

    }

    public function login() : bool
    {
        //todo сделать логин - если это возможно
        return true;
    }

    public function parse()
    {
        $pageNum = 0;
        $this->initCounter();

        do {
            $pageNum++;
            Log::stack(['stderr', $this->logChannel])->info('Parse page #'.$pageNum);
            $html = $this->fetchPage($pageNum);
            $this->storage->put($pageNum.'.html',$html);
            if (!$this->isLoggedIn($html)) {
                Log::stack(['stderr', $this->logChannel])->error('User is not authorized');
                throw new ParserException($this->parserName.' : User is not authorized (most likely cookie has expired)', 401 );
            }

            $this->fetchDomains($html);

            sleep(5);

        } while ($this->checkNextPage($html));

    }

    private function fetchDomains(string $html)
    {
        $pageDom = new Crawler($html);

        $rows = $pageDom->filter('table.c-table tbody tr')->each(function ($content) {
            return $content->html();
        });

        foreach ($rows as $row) {
            $domain = $this->fetchDomain($row);
            $this->upsertDomain($domain);
        }

        dd('123');

    }


    private function fetchDomain(string $html) : CollaboratorDomain
    {
        $this->storage->put('row.html',$html);
        $rowDom = new Crawler($html);

        $domain = new CollaboratorDomain($rowDom->filter('.link')->text());

        $domain->setId(intval($rowDom->filter('.grid-group-checkbox')->attr('value')));

        if ($rowDom->filter('.creator-price_catalog')->count() > 0) {
            $domain->setPrice($rowDom->filter('.creator-price_catalog')->attr('data-publication'));
        }

        if ($rowDom->filter('ul.list-traffic li')->count() > 0) {
            $domain->setTraffic($rowDom->filter('ul.list-traffic li')->first()->text());
        }

        $niches = $rowDom->filter('.c-t-theme__tags .tag')->each(function ($content) {
            return $content->text();
        });
        $domain->setNiches($niches);

        return $domain;
    }

    public function upsertDomain(CollaboratorDomain $collaboratorDomain)
    {
        $domain = Domain::updateOrCreate(
            ['url' => $collaboratorDomain->getDomain()],
            //todo update траффик когда понятно какой брать
            ['url' => $collaboratorDomain->getDomain()]
        );

        $collaborator = \App\Models\Collaborator::updateOrCreate(
            [
                'id' => $collaboratorDomain->getId()
            ],
            [
                'domain_id' => $domain->id,
                'url' => $collaboratorDomain->getDomain(),
                'price' => $collaboratorDomain->getPrice(),
                'theme' => $collaboratorDomain->getNiches(),
                'traffic' => $collaboratorDomain->getTraffic(),
                'updated_at' => Carbon::now()
            ]
        );

        //Сравниваем время создания и апдейта, для счетчика добавленных обновленных
        if ($collaborator->created_at == $collaborator->updated_at) {
            $state = 'new';
            $this->counter->incNew();
        } else {
            $state = 'updated';
            $this->counter->incUpdated();
        }

        Log::stack(['stderr', $this->logChannel])->info('Domain: '.$collaboratorDomain->getDomain().' | State: '.$state.' | Progress: '.$this->counter->getCurrent().'/'.$this->counter->getTotal().' | Added: '.$this->counter->getNew().' | Updated: '.$this->counter->getUpdated());

    }

    public function isLoggedIn(string $html) : bool
    {
        return str_contains($html,static::LOGGED_IN_NEEDLE);
    }

    private function setupHttpClient() : void
    {

        $this->checkCookie();

        $this->httpClient = Http::withHeaders([
            'accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.9',
            'accept-encoding' => 'deflate, br',
            'accept-language' => 'ru-RU,ru;q=0.9,en-US;q=0.8,en;q=0.7,uk;q=0.6,pl;q=0.5',
            'cache-control' => 'no-cache',
            'cookie' =>  Storage::get('cookies/'.$this->parserName.'.txt'),
            'pragma' => 'no-cache',
            'sec-ch-ua' => '".Not/A)Brand";v="99", "Google Chrome";v="103", "Chromium";v="103"',
            'sec-ch-ua-mobile' => '?0',
            'sec-ch-ua-platform' => '"Windows"',
            'sec-fetch-dest' => 'document',
            'sec-fetch-mode' => 'navigate',
            'sec-fetch-site' => 'none',
            'sec-fetch-user' => '?1',
            'upgrade-insecure-requests' => 1,
            'user-agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/103.0.0.0 Safari/537.36'
        ]);

    }

    private function checkCookie() : void
    {
        if (!Storage::exists('cookies/'.$this->parserName.'.txt')) {
            throw new ParserException($this->parserName.' : Missing parser cookie file '.Storage::path('cookies/'.$this->parserName.'.txt'), 404);
        }
    }

    private function checkNextPage(string $html) : bool
    {
        return str_contains($html,self::NEXT_PAGE_NEEDLE);
    }

    private function initCounter() : void
    {
        $html = $this->httpClient->get('https://collaborator.pro/ua/catalog/creator/article?page=1')->body();
        $dom = new Crawler($html);
        $this->counter = new ParserProgressCounter(DomainsHelper::getPriceFromString($dom->filter('.filter-panel b')->text()));
    }

    private function fetchPage(int $num) : string
    {
        return $this->httpClient->get('https://collaborator.pro/ua/catalog/creator/article?page='.$num.'&per-page=100')->body();
    }

}