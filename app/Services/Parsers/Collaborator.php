<?php

namespace App\Services\Parsers;

use App\Dto\Domain as DomainDto;
use App\Models\CollaboratorDomain;
use App\Enums\Currency;
use App\Models\Domain;
use App\Helpers\DomainsHelper;
use App\Models\StockDomain;
use Illuminate\Support\Facades\Log;
use App\Extensions\Symfony\DomCrawler\Crawler;
use Illuminate\Support\Str;

class Collaborator extends DomParser
{
    const LOGGED_IN_NEEDLE = '/images/icons/logout.svg';
    const NEXT_PAGE_NEEDLE = '<li class="page-item_next"><a class="page-link"';

    protected function getDomainsTotal() : int
    {
        $html = $this->httpClient->get('https://collaborator.pro/ua/catalog/creator/article')->body();
        $this->logStorage->put('TotalCount.html',$html);
        $this->checkLoggedIn($html);
        $dom = new Crawler($html);
        if ($dom->filter('.filter-panel b')->count() > 0) {
            $domainsCount = DomainsHelper::getPriceFromString($dom->filter('.filter-panel b')->text());
        } else {
            Log::stack(['stderr', $this->logChannel])->warning('Domain total count not found');
            $domainsCount = 0;
        }

        return $domainsCount;
    }

    protected function fetchDomainsPage(int $pageNum) : string
    {
        return $this->httpClient->get('https://collaborator.pro/ua/catalog/creator/article?page='.$pageNum.'&per-page=100&sort=url')->body();
    }

    protected function fetchDomainRows(string $html) : array
    {
        $pageDom = new Crawler($html);

        $rows = $pageDom->filter('table.c-table tbody tr')->each(function ($content) {
            return $content->html();
        });

        return $rows;
    }

    protected function fetchDomainData(string $html) : DomainDto
    {

        $rowDom = new Crawler($html);

        $domain = new DomainDto($rowDom->filter('.link')->text());

        //ID в бирже
        $domain->setStockId(intval($rowDom->filter('.grid-group-checkbox')->attr('value')));

        //цена, иногда не бывает
        if ($rowDom->filter('.creator-price_catalog')->count() > 0) {
            $price = DomainsHelper::getPriceFromString($rowDom->filter('.creator-price_catalog')->attr('data-publication'));
        } else {
            $price = 0;
        }
        $domain->setPrice($price, Currency::UAH);

        //траффик
        $domain->setTraffic($rowDom->fetchOptionalText('ul.list-traffic li'));

        //DR
        $dr = $rowDom->filter('td:nth-child(6)')->text();
        if ($dr != "-") {
            $domain->setDr($dr);
        }

        //Theme
        $niches = $rowDom->filter('.c-t-theme__tags .tag')->each(function ($content) {
            return $content->text();
        });
        $domain->setTheme(implode("; ", $niches));

        return $domain;
    }


    protected function upsertDomain(DomainDto $domainDto) : StockDomain
    {
        $domain = Domain::updateOrCreate(
            ['domain' => $domainDto->getName()],
            //todo update траффик когда понятно какой брать
            ['domain' => $domainDto->getName()]
        );

        $collaboratorDomain = CollaboratorDomain::updateOrCreate(
            [
                'id' => $domainDto->getStockId()
            ],
            [
                'domain_id' => $domain->id,
                'domain' => $domainDto->getName(),
                'price' => $domainDto->getPrice(),
                'theme' => $domainDto->getTheme(),
                'dr' => $domainDto->getDr(),
                'traffic' => $domainDto->getTraffic(),
                'updated_at' => now(),
                'deleted_at' => null
            ]
        );

        return $collaboratorDomain;

    }

    protected function postUpdateActions(): void
    {
        CollaboratorDomain::query()->whereDate('updated_at', '<=', now()->subDays(1)->toDateTimeString())->delete();
    }
}