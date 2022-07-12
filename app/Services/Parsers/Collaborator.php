<?php

namespace App\Services\Parsers;

use App\Dto\Domain as DomainDto;
use App\Dto\ParserProgressCounter;
use App\Enums\Currency;
use App\Models\Domain;
use App\Exceptions\ParserException;
use App\Helpers\DomainsHelper;
use App\Models\StockDomain;
use Carbon\Carbon;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Http;
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
            $domain->setPrice($rowDom->filter('.creator-price_catalog')->attr('data-publication'), Currency::UAH);
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

    protected function getDomainsTotal() : int
    {
        $html = $this->httpClient->get('https://collaborator.pro/ua/catalog/creator/article?page=1')->body();
        $dom = new Crawler($html);
        return DomainsHelper::getPriceFromString($dom->filter('.filter-panel b')->text());
    }

    protected function fetchDomainsPage(int $pageNum) : string
    {
        return $this->httpClient->get('https://collaborator.pro/ua/catalog/creator/article?page='.$pageNum.'&per-page=100&sort=url')->body();
    }

}