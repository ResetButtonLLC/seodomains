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
    protected float $currencyRate;

    public function __construct()
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
        $this->setCurrencyRate();
    }

    //Оснвная функция парсинга "parse", зависит от типа парсера
    abstract protected function upsertDomain(Domain $domain) : StockDomain;
    abstract protected function postUpdateActions() : void;

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

    //Функция проверки валидности домена и занесения в БД
    protected function processDomain(Domain $domainDto)
    {

        //todo пустое имя должно вызывать ошибку
        //Проверяем валидноcть спарсенных данных так как может быть "URL скрыт", нету цены и т.д.
        if ($domainDto->isDataOk()) {

            //Если с данными все ОК, то добавляем/обновляем домен
            $domainDto->convertPrice($this->getCurrencyRate());
            $stockDomain = $this->upsertDomain($domainDto);

            //Сравниваем время создания и апдейта, Если время совпадает, то домен новый, если нет - то домен уже был.
            ($stockDomain->created_at == $stockDomain->updated_at) ? $this->counter->incNew() : $this->counter->incUpdated();
        } else {
            //Если данные невалидны, то обновляем счетчик пропущеных
            $this->counter->incSkipped();
        }

        Log::stack(['stderr', $this->logChannel])->info('['.$this->counter->getCurrent().'/'.$this->counter->getTotal().'] Domain: '.$domainDto->getName().' | State: '.$this->counter->getLastAddedTo().' | Added: '.$this->counter->getNew().' | Updated: '.$this->counter->getUpdated().' | Skipped:'. $this->counter->getSkipped());
    }

    protected function finishActions() : void
    {
        $this->postUpdateActions();
        Log::stack(['stderr', $this->logChannel])->info('Finished');
        Update::setUpdatedTime($this->parserName);
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

    protected function checkLoggedIn(string $html) : bool
    {
        if (!$this->isLoggedIn($html)) {
            Log::stack(['stderr', $this->logChannel])->error('User is not authorized');
            throw new ParserException($this->parserName.' : User is not authorized (most likely cookie has expired)', 401 );
        }

        return true;
    }

    protected function getPause() : int
    {
        return $this->pause;
    }

    //По дефолту валюта UAH, поэтому курс равен 1;
    protected function setCurrencyRate() : void
    {
        $this->currencyRate = 1;
    }

    protected function getCurrencyRate() : float
    {
        return $this->currencyRate;
    }


}