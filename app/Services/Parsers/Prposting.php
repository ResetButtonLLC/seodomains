<?php

namespace App\Services\Parsers;

use App\Dto\Domain as DomainDto;
use App\Enums\Currency;
use App\Helpers\DomainsHelper;
use App\Models\Domain;
use App\Models\PrpostingDomain;
use App\Models\StockDomain;
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
        return $this->httpClient->get('https://prposting.com/ru/projects/14959/publishers?page='.$pageNum)->body();
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

        $domain = new DomainDto($rowDom->filter('td.is-paddingless tr td')->text());

        //ID в бирже
        $domain->setStockId(intval($rowDom->filter('td.is-narrow div')->attr(':site-id')));

        //Цена
        if ($rowDom->filter('td.is-narrow div')->count() > 0) {
            $price = DomainsHelper::getPriceFromString($rowDom->filter('td.is-narrow div')->attr('price'));
            $domain->setPrice($price,Currency::UAH);
        }

        //Traffic SW
        if ($rowDom->filter('td.is-paddingless:nth-child(3) tr td:nth-child(2)')->count() > 0) {
            $domain->setTraffic($rowDom->filter('td.is-paddingless:nth-child(3) tr td:nth-child(2)')->first()->text());
        }

        $theme = $rowDom->filter('td.is-paddingless tr:nth-child(2)')->text();

        $domain->setTheme($theme);

        return $domain;
    }

    protected function upsertDomain(DomainDto $domainDto) : StockDomain
    {
        $domain = Domain::updateOrCreate(
            ['domain' => $domainDto->getName()],
            //todo update траффик когда понятно какой брать
            ['domain' => $domainDto->getName()]
        );

        $prpostingDomain = PrpostingDomain::updateOrCreate(
            [
                'id' => $domainDto->getStockId()
            ],
            [
                'domain_id' => $domain->id,
                'name' => $domainDto->getName(),
                'price' => $domainDto->getPrice(),
                'theme' => $domainDto->getTheme(),
                'traffic' => $domainDto->getTraffic(),
                'updated_at' => now()
            ]
        );

        return $prpostingDomain;

    }

    protected function postUpdateActions(): void
    {
        PrpostingDomain::query()->whereDate('updated_at', '<=', now()->subDays(2)->toDateTimeString())->delete();
    }
}