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

    protected function fetchDomainData(string $html) : CollaboratorDomain
    {

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


    protected function upsertDomain(\App\Dto\Domain $collaboratorDomain) : void
    {
        $domain = Domain::updateOrCreate(
            ['url' => $collaboratorDomain->getName()],
            //todo update траффик когда понятно какой брать
            ['url' => $collaboratorDomain->getName()]
        );

        $collaborator = \App\Models\Collaborator::updateOrCreate(
            [
                'id' => $collaboratorDomain->getId()
            ],
            [
                'domain_id' => $domain->id,
                'url' => $collaboratorDomain->getName(),
                'price' => $collaboratorDomain->getPrice(),
                'theme' => $collaboratorDomain->getNiches(),
                'traffic' => $collaboratorDomain->getTraffic(),
                'updated_at' => Carbon::now()
            ]
        );

        //Сравниваем время создания и апдейта, для счетчика добавленных обновленных
        if ($collaborator->created_at == $collaborator->updated_at) {
            $this->counter->incNew();
        } else {
            $this->counter->incUpdated();
        }
    }

    protected function getCounterMax() : int
    {
        $html = $this->httpClient->get('https://collaborator.pro/ua/catalog/creator/article?page=1')->body();
        $dom = new Crawler($html);
        return DomainsHelper::getPriceFromString($dom->filter('.filter-panel b')->text());
    }

    protected function fetchDomainsPage(int $num) : string
    {
        return $this->httpClient->get('https://collaborator.pro/ua/catalog/creator/article?page='.$num.'&per-page=100')->body();
    }

}