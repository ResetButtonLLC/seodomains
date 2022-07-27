<?php

namespace App\Services\Parsers;

use App\Dto\Domain;
use App\Dto\ParserProgressCounter;
use App\Exceptions\ParserException;
use App\Models\StockDomain;
use App\Models\Update;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Psr\Log\LoggerInterface;

abstract class Parser
{
    const LOGGED_IN_NEEDLE = 'replace this with your needle';
    const NEXT_PAGE_NEEDLE = 'replace this with your needle';
    protected string $parserName;
    protected int $pause;
    protected LoggerInterface $logChannel;
    protected Filesystem $logStorage;
    protected PendingRequest $httpClient;
    protected ParserProgressCounter $counter;

    final public function __construct()
    {
        $classParts = explode('\\', get_class($this));
        $this->parserName = strtolower(array_pop($classParts));

        $this->logChannel = Log::build([
            'driver' => 'daily',
            'path' => storage_path('app/parsers/'.$this->parserName.'/logs/'.$this->parserName.'.log'),
        ]);

        Log::stack(['stderr', $this->logChannel])->info('Fire up parser '.$this->parserName);

        $this->logStorage = Storage::build([
            'driver' => 'local',
            'root' => storage_path('app/parsers/'.$this->parserName.'/logs/pages/'),
        ]);

        $this->setupHttpClient();
        $this->setPause();
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
                //todo пустое имя должно вызывать ошибку

                //Проверяем валидноcть спарсенных данных так как может быть "URL скрыт", нету цены и т.д.
                if ($domainDto->isDataOk()) {

                    //Если с данными все ОК, то добавляем/обновляем домен
                    $stockDomain = $this->upsertDomain($domainDto);

                    //Сравниваем время создания и апдейта, Если время совпадает, то домен новый, если нет - то домен уже был.
                    ($stockDomain->created_at == $stockDomain->updated_at) ? $this->counter->incNew() : $this->counter->incUpdated();
                } else {
                    //Если данные невалидны, то обновляем счетчик пропущеных
                    $this->counter->incSkipped();
                }

                Log::stack(['stderr', $this->logChannel])->info('Domain: '.$domainDto->getName().' | State: '.$this->counter->getLastAddedTo().' | Progress: '.$this->counter->getCurrent().'/'.$this->counter->getTotal().' | Added: '.$this->counter->getNew().' | Updated: '.$this->counter->getUpdated().' | Skipped:'. $this->counter->getSkipped());

            }

            Log::stack(['stderr', $this->logChannel])->info('Sleeping '.$this->getPause().' seconds');
            sleep($this->getPause());
            $this->setPause();
            $pageNum++;

        } while ($this->isNextPage($html));

        Log::stack(['stderr', $this->logChannel])->info('Finished');
        Update::setUpdatedTime($this->parserName);

        $this->postUpdateActions();

    }

    protected function checkCookie() : void
    {
        if (!Storage::exists('parsers/'.$this->parserName.'/cookie/'.$this->parserName.'.txt')) {
            throw new ParserException($this->parserName.' : Missing parser cookie file '.Storage::path('parsers/'.$this->parserName.'/cookie/'.$this->parserName.'.txt'), 404);
        }
    }

    protected function isLoggedIn(string $html) : bool
    {
        return str_contains($html,static::LOGGED_IN_NEEDLE);
    }

    protected function isNextPage(string $html) : bool
    {
        return str_contains($html,static::NEXT_PAGE_NEEDLE);
    }

    protected function setPause() : void
    {
        $this->pause = 5;
    }

    protected function checkLoggedIn(string $html) : void
    {
        if (!$this->isLoggedIn($html)) {
            Log::stack(['stderr', $this->logChannel])->error('User is not authorized');
            throw new ParserException($this->parserName.' : User is not authorized (most likely cookie has expired)', 401 );
        }
    }

    protected function getPause() : int
    {
        return $this->pause;
    }

    abstract protected function getDomainsTotal() : int;
    abstract protected function fetchDomainsPage(int $pageNum) : string;
    abstract protected function fetchDomainRows(string $html) : array;
    abstract protected function fetchDomainData(string $html) : Domain;
    abstract protected function upsertDomain(Domain $domain) : StockDomain;
    abstract protected function postUpdateActions() : void;

}