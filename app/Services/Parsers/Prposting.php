<?php

namespace App\Services\Parsers;

use App\Dto\Domain as DomainDto;
use App\Enums\Currency;
use App\Helpers\DomainsHelper;
use App\Models\Domain;
use App\Models\PrpostingDomain;
use App\Models\StockDomain;
use Illuminate\Support\Facades\Log;
use App\Extensions\Symfony\DomCrawler\Crawler;

class Prposting extends DomParser
{
    const LOGGED_IN_NEEDLE = 'https://prposting.com/ru/logout';
    const NEXT_PAGE_NEEDLE = 'rel="next">Следующая</a>';

    protected function getDomainsTotal() : int
    {
        $html = $this->httpClient->get('https://prposting.com/ru/publishers?page=1')->body();
        $this->logStorage->put('TotalCount.html',$html);
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
        $price = DomainsHelper::getPriceFromString($rowDom->filter('td.is-narrow div')->attr('price'));
        $domain->setPrice($price,Currency::UAH);

        //Traffic Similarweb
        $domain->setTraffic($rowDom->fetchOptionalText('td.is-paddingless:nth-child(3) tr td:nth-child(2)'));

        //Ahrefs DR
        $dr = $rowDom->fetchOptionalText('td.is-paddingless:nth-child(2) tr td.has-text-right');
        if (is_numeric($dr)) {
            $domain->setDr($rowDom->fetchOptionalText('td.is-paddingless:nth-child(2) tr td.has-text-right'));
        }

        //Majestic CF & TF
        $tf = $rowDom->fetchOptionalText('td.is-paddingless:nth-child(5) tr td.has-text-right');
        if (is_numeric($tf)) {
            $domain->setTf($tf);
        }

        $cf = $rowDom->fetchOptionalText('td.is-paddingless:nth-child(5) table tr:nth-child(2) td:nth-child(2)');
        if (is_numeric($cf)) {
            $domain->setCf($cf);
        }

        //Theme
        $theme = $rowDom->filter('td.is-paddingless tr:nth-child(2)')->text();
        $domain->setTheme($theme);

        return $domain;
    }

    protected function upsertDomain(DomainDto $domainDto) : StockDomain
    {
        $domain = Domain::updateOrCreate(
            ['domain' => $domainDto->getName()],
            ['domain' => $domainDto->getName()]
        );

        $prpostingDomain = PrpostingDomain::updateOrCreate(
            [
                //используем stockID, так как обнаружился домен с двойным ID
                'id' => $domainDto->getStockId(),
            ],
            [
                'id' => $domainDto->getStockId(),
                'name' => $domainDto->getName(),
                'domain_id' => $domain->id,
                'price' => $domainDto->getPrice(),
                'theme' => $domainDto->getTheme(),
                'traffic' => $domainDto->getTraffic(),
                'dr' => $domainDto->getDr(),
                'cf' => $domainDto->getCf(),
                'tf' => $domainDto->getTf(),
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