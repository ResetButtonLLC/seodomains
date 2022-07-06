<?php

namespace App\Services\Parsers;

use App\Exceptions\ParserException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Http;
use Illuminate\Contracts\Filesystem\Filesystem;
use Psr\Log\LoggerInterface;

class Collaborator
{

    const LOGGED_IN_NEEDLE = '/images/icons/logout.svg';
    protected string $parserName;
    protected LoggerInterface $logChannel;
    protected Filesystem $storage;
    protected PendingRequest $httpClient;

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

    public function login() : bool
    {
        //todo сделать логин - если это возможно
        return true;
    }

    public function parse()
    {
        $pageNum = 0;

        do {
            $pageNum++;
            Log::stack(['stderr', $this->logChannel])->info('Parse page #'.$pageNum);
            $html = $this->fetchPage($pageNum);
            $this->storage->put($pageNum.'.html',$html);
            if (!$this->isLoggedIn($html)) {
                Log::stack(['stderr', $this->logChannel])->error('User is not authorized');
                throw new ParserException($this->parserName.' : User is not authorized', 401 );
            }

            //todo extract domains and data

            dd('123');
        } while ($this->checkNextPage($html));

    }

    public function isLoggedIn(string $html) : bool
    {
        return str_contains($html,static::LOGGED_IN_NEEDLE);
    }

    private function setupHttpClient() : void
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

    private function checkCookie() : void
    {
        if (!Storage::exists('cookies/'.$this->parserName.'.txt')) {
            throw new ParserException($this->parserName.' : Missing parser cookie file '.Storage::path('cookies/'.$this->parserName.'.txt'), 404);
        }
    }

    private function checkNextPage(string $html) : bool
    {
        $needle = '<li class="page-item_next"><a class="page-link"';
        return str_contains($html,$needle);
    }

    private function writeLog(string $message) : void
    {

    }

    private function fetchPage(int $num) : string
    {
        return $this->httpClient->get('https://collaborator.pro/ua/catalog/creator/article?page='.$num.'&per-page=100')->body();
    }

}