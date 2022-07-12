<?php

namespace App\Services\Parsers;

use App\Dto\Domain as DomainDto;
use App\Enums\Currency;
use App\Helpers\DomainsHelper;
use App\Models\Domain;
use App\Models\StockDomain;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Symfony\Component\DomCrawler\Crawler;

class Prposting extends Parser
{
    const LOGGED_IN_NEEDLE = 'https://prposting.com/ru/logout';
    const NEXT_PAGE_NEEDLE = 'rel="next">Следующая</a>';

    protected function getDomainsTotal() : int
    {
        $html = $this->httpClient->get('https://prposting.com/ru/publishers?page=1')->body();
        $this->storage->put('TotalCount.html',$html);
        $this->checkLoggedIn($html);
        $dom = new Crawler($html);
        if ($dom->filter('div.grid-content template')->count() > 0) {
            $domainsCount = DomainsHelper::getPriceFromString($dom->filter('div.grid-content template')->text());
        } else {
            Log::stack(['stderr', $this->logChannel])->warning('Domain total count not found');
            $domainsCount = 0;
        }

        return $domainsCount;
    }

    protected function fetchDomainsPage(int $pageNum) : string
    {
        return $this->httpClient->get('https://prposting.com/ru/publishers?page='.$pageNum)->body();
    }

    protected function fetchDomainRows(string $html) : array
    {
        $pageDom = new Crawler($html);

        $rows = $pageDom->filter('tr.is-size-7')->each(function ($content) {
            return $content->html();
        });

        return $rows;
    }

    protected function fetchDomainData(string $html) : DomainDto
    {

        $rowDom = new Crawler($html);

        $domain = new DomainDto($rowDom->filter('a.is-size-6')->text());

        $domain->setStockId(intval($rowDom->filter('div[price^="$"]')->attr(':site-id')));

        if ($rowDom->filter('div[price^="$"]')->count() > 0) {
            $domain->setPrice($rowDom->filter('div[price^="$"]')->attr('price'), Currency::USD);
        }

        //Traffic SW
        if ($rowDom->filter('td.is-paddingless:nth-child(3) tr td:nth-child(2)')->count() > 0) {
            $domain->setTraffic($rowDom->filter('td.is-paddingless:nth-child(3) tr td:nth-child(2)')->first()->text());
        }

        $niches = $rowDom->filter('td.is-paddingless tr:nth-child(2)')->text();

        $domain->setNiches($niches);

        return $domain;
    }

    protected function upsertDomain(DomainDto $domain) : StockDomain
    {
        $dBdomain = Domain::updateOrCreate(
            ['url' => $domain->getName()],
            //todo update траффик когда понятно какой брать
            ['url' => $domain->getName()]
        );

        $collaboratorDbDomain = \App\Models\Collaborator::updateOrCreate(
            [
                'id' => $domain->getStockId()
            ],
            [
                'domain_id' => $dBdomain->id,
                'url' => $domain->getName(),
                'price' => $domain->getPrice(),
                'theme' => $domain->getNiches(),
                'traffic' => $domain->getTraffic(),
                'updated_at' => Carbon::now()
            ]
        );

        return $collaboratorDbDomain;

    }

}