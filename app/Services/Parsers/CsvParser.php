<?php

namespace App\Services\Parsers;

use App\Dto\Domain;
use App\Dto\ParserProgressCounter;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Rap2hpoutre\FastExcel\FastExcel;

abstract class CsvParser extends Parser
{

    protected Filesystem $csvStorage;

    public function __construct()
    {

        parent::__construct();

        $this->csvStorage = Storage::build([
            'driver' => 'local',
            'root' => storage_path('app/parsers/'.$this->parserName.'/csv/'),
        ]);

    }

    public function parse() : void
    {
        $this->downloadCSV();

        $csvDomains = (new FastExcel)->import($this->csvStorage->path($this->parserName.'.csv'));
        $this->counter = new ParserProgressCounter($csvDomains->count());

        Log::stack(['stderr', $this->logChannel])->info('Process '.$csvDomains->count().' domains from CSV File');
        foreach ($csvDomains as $row) {
            //Получаем данные домена
            $domainDto = $this->fetchDomainData($row);
            $this->processDomain($domainDto);
        }

        $this->finishActions();

    }

    abstract protected function downloadCSV() : void;
    abstract protected function fetchDomainData(array $row) : Domain;


}