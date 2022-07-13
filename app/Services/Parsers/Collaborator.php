<?php

namespace App\Services\Parsers;

use App\Dto\Domain as DomainDto;
use App\Models\CollaboratorDomain;
use App\Enums\Currency;
use App\Models\Domain;
use App\Helpers\DomainsHelper;
use App\Models\StockDomain;
use Illuminate\Support\Facades\Log;
use Symfony\Component\DomCrawler\Crawler;

class Collaborator extends Parser
{
    const LOGGED_IN_NEEDLE = '/images/icons/logout.svg';
    const NEXT_PAGE_NEEDLE = '<li class="page-item_next"><a class="page-link"';

    public function login() : bool
    {
        //todo сделать логин - если это возможно
        return true;
    }

    protected function getDomainsTotal() : int
    {
        $html = $this->httpClient->get('https://collaborator.pro/ua/catalog/creator/article')->body();
        $this->storage->put('TotalCount.html',$html);
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

        $domain->setStockId(intval($rowDom->filter('.grid-group-checkbox')->attr('value')));

        if ($rowDom->filter('.creator-price_catalog')->count() > 0) {
            $price = DomainsHelper::getPriceFromString($rowDom->filter('.creator-price_catalog')->attr('data-publication'));
            $domain->setPrice($price, Currency::UAH);
        }

        if ($rowDom->filter('ul.list-traffic li')->count() > 0) {
            $domain->setTraffic($rowDom->filter('ul.list-traffic li')->first()->text());
        }

        $niches = $rowDom->filter('.c-t-theme__tags .tag')->each(function ($content) {
            return $content->text();
        });
        $domain->setTheme(implode("; ", $niches));

        return $domain;
    }


    protected function upsertDomain(DomainDto $domainDto) : StockDomain
    {
        $domain = Domain::updateOrCreate(
            ['url' => $domainDto->getName()],
            //todo update траффик когда понятно какой брать
            ['url' => $domainDto->getName()]
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
                'traffic' => $domainDto->getTraffic(),
                'updated_at' => now()
            ]
        );

        return $collaboratorDomain;

    }

    protected function postUpdateActions(): void
    {
        CollaboratorDomain::query()->whereDate('updated_at', '<=', now()->subDays(1)->toDateTimeString())->delete();
    }
}