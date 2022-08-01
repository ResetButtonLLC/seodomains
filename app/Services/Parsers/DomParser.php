<?php

namespace App\Services\Parsers;

use App\Dto\Domain;
use App\Dto\ParserProgressCounter;
use Illuminate\Support\Facades\Log;

abstract class DomParser extends Parser
{

    public function parse($pageNum = 1) : void
    {

        $this->counter = new ParserProgressCounter($this->getDomainsTotal());

        do {
            Log::stack(['stderr', $this->logChannel])->info('Parse page #'.$pageNum);
            $html = $this->fetchDomainsPage($pageNum);
            $this->logStorage->put($pageNum.'.html',$html);
            $this->checkLoggedIn($html);

            //Получаем блоки DOM содержащие информацию о доменах
            $domainRows = $this->fetchDomainRows($html);

            foreach ($domainRows as $row) {
                $this->logStorage->put('row.html',$row);
                //Получаем данные домена
                $domainDto = $this->fetchDomainData($row);
                $this->processDomain($domainDto);
            }

            Log::stack(['stderr', $this->logChannel])->info('Sleeping '.$this->getPause().' seconds');
            sleep($this->getPause());
            $this->setPause();
            $pageNum++;

        } while ($this->isNextPage($html));

        $this->finishActions();

    }

    abstract protected function getDomainsTotal() : int;
    abstract protected function fetchDomainsPage(int $pageNum) : string;
    abstract protected function fetchDomainRows(string $html) : array;
    abstract protected function fetchDomainData(string $html) : Domain;

}