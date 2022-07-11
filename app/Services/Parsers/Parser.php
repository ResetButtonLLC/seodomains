<?php

namespace App\Services\Parsers;

use App\Dto\Domain;
use App\Dto\ParserProgressCounter;
use App\Exceptions\ParserException;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Psr\Log\LoggerInterface;

abstract class Parser
{
    const LOGGED_IN_NEEDLE = '';
    const NEXT_PAGE_NEEDLE = '';
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

    protected function setupHttpClient() : void
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

    protected function checkCookie() : void
    {
        if (!Storage::exists('cookies/'.$this->parserName.'.txt')) {
            throw new ParserException($this->parserName.' : Missing parser cookie file '.Storage::path('cookies/'.$this->parserName.'.txt'), 404);
        }
    }

    public function parse() : void
    {
        $pageNum = 0;
        $sleep = 5;
        $this->counter = new ParserProgressCounter($this->getCounterMax());

        do {
            $pageNum++;
            Log::stack(['stderr', $this->logChannel])->info('Parse page #'.$pageNum);
            $html = $this->fetchDomainsPage($pageNum);
            $this->storage->put($pageNum.'.html',$html);
            if (!$this->isLoggedIn($html)) {
                Log::stack(['stderr', $this->logChannel])->error('User is not authorized');
                throw new ParserException($this->parserName.' : User is not authorized (most likely cookie has expired)', 401 );
            }

            //Получаем блоки DOM содержащие информацию о доменах
            $domainRows = $this->fetchDomainRows($html);

            foreach ($domainRows as $row) {
                $this->storage->put('row.html',$html);
                //Получаем данные домена
                $domain = $this->fetchDomainData($row);
                //Проверяем валидноcть спарсенных данных так как может быть "URL скрыт" или нету цены
                if ($domain->isDataOk()) {
                    $this->upsertDomain($domain);
                } else {
                    $this->counter->incSkipped();
                }

                Log::stack(['stderr', $this->logChannel])->info('Domain: '.$domain->getName().' | State: '.$this->counter->getLastAddedTo().' | Progress: '.$this->counter->getCurrent().'/'.$this->counter->getTotal().' | Added: '.$this->counter->getNew().' | Updated: '.$this->counter->getUpdated().' | Skipped:'. $this->counter->getSkipped());

            }

            sleep($sleep);

        } while ($this->checkNextPage($html));

    }

    protected function isLoggedIn(string $html) : bool
    {
        return str_contains($html,static::LOGGED_IN_NEEDLE);
    }

    protected function checkNextPage(string $html) : bool
    {
        return str_contains($html,self::NEXT_PAGE_NEEDLE);
    }

    abstract protected function getCounterMax() : int;
    abstract protected function fetchDomainsPage(int $pageNum) : string;
    abstract protected function fetchDomainRows(string $html) : array;
    abstract protected function fetchDomainData(string $html) : Domain;
    abstract protected function upsertDomain(Domain $domain) : void;

}