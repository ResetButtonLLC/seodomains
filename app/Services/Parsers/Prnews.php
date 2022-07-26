<?php

namespace App\Services\Parsers;

use App\Dto\Domain;
use App\Dto\ParserProgressCounter;
use App\Exceptions\ParserException;
use App\Models\StockDomain;
use App\Models\Update;
use HeadlessChromium\Page;
use http\Exception\RuntimeException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use HeadlessChromium\BrowserFactory;
use Illuminate\Http\File;

class Prnews
{
    final public function __construct()
    {
        $classParts = explode('\\', get_class($this));
        $this->parserName = strtolower(array_pop($classParts));

        $this->logChannel = Log::build([
            'driver' => 'daily',
            'path' => storage_path('logs/parsers/'.$this->parserName.'/'.$this->parserName.'.log'),
        ]);

        Log::stack(['stderr', $this->logChannel])->info('Fire up parser '.$this->parserName);

        $this->storagePath = 'logs/parsers/'.$this->parserName.'/pages';

    }

    public function parse() : void
    {

        $this->downloadCSV();
        /*
        $this->setupHttpClient();
        $this->httpClient->sink('prnews.csv.zip')->get('https://cdn01.prnews.io/tmp/q1Hu45i0h45Jl10m74gH0J1vbMs/1Er5fe7_pro_all_platforms_ru.csv.zip?1658825309');
        Storage::putFile('csv', new File('prnews.csv.zip'));

        if (!config('parsers.prnews.login') || !config('parsers.prnews.password')) {
            throw new ParserException('Missing Prnews Login/Password in .env file');
        }

        dd('123');
        */

        //Бразуерная эмуляция может вылетать с ошибкой, если при логине вылазит капча или прокси медленный поэтому, операцию нужно выполнять несколько раз
        /*
        $tries = 30;
        do {
            $csvLink = $this->getCsvLink();
            $tries--;
            if (!$tries) {
                throw new ParserException('No chrome retries left while obtaining CSV link');
            }
        } while ($tries || !$csvLink);
        */




    }

    //Логинимся в аккаунт и скачиваем CSV
    private function getCsvLink() : string
    {

        $proxy = 'p.webshare.io:'.rand(10000,11000);
        Log::stack(['stderr', $this->logChannel])->info('Initializing chrome with proxy '.$proxy);

        $browserFactory = new BrowserFactory('chromium');

        // starts headless chrome
        $browser = $browserFactory->createBrowser([
            'connectionDelay' => 0.5,
            'debugLogger'     => 'php://stdout', // will enable verbose mode,
            'noSandbox' => true,
            'userAgent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:102.0) Gecko/20100101 Firefox/102.0',
            'proxyServer' => $proxy
        ]);

        try {
            // creates a new page and navigate to an URL
            $page = $browser->createPage();
            $page->navigate('https://prnews.io/login/')->waitForNavigation();
            $page->screenshot()->saveToFile(Storage::path($this->storagePath.'/1-LoginPage.jpg'));
            $page->mouse()->find('input[name=mail]')->click();
            $page->keyboard()->typeText(config('parsers.prnews.login'));
            $page->mouse()->find('input[name=password]')->click();
            $page->keyboard()->typeText(config('parsers.prnews.password'));
            $page->mouse()->find('input[type=submit]')->click();
            sleep(10);
            $page->screenshot()->saveToFile(Storage::path($this->storagePath.'/2-AfterLoginPage.jpg'));

            $page->navigate('https://prnews.io/ru/sites/');
            sleep(10);
            $page->screenshot()->saveToFile(Storage::path($this->storagePath.'/3-SiteListPage.jpg'));

            $page->mouse()->find('#pro_export')->click();
            $downloadTag = $page->dom()->querySelector('#pro_export_success_full a');
            $csvLink = $downloadTag->getAttribute('href');

        } catch (\Exception $e) {
            Log::stack(['stderr', $this->logChannel])->error('Parsing Failed with error: '.$e->getMessage());
            $csvLink = '';
        }
        finally {
            $browser->close();
        }

        Log::stack(['stderr', $this->logChannel])->info('Got link for CSV '.$csvLink);

        return $csvLink;

    }

    protected function downloadCSV() : void
    {

        //Delete all zip files
        $files = Storage::allFiles('/csv');
        $zipfiles = array_filter($files, fn($f) => ends_with($f,'.zip'));
        /*
        $zipfiles = array_filter($files, function($f) {
            return ends_with($f,'.zip');
        });
        */

        Storage::delete($zipfiles);

        dd($zipfiles);

        //Download new file
        $browserFactory = new BrowserFactory('chromium');

        // starts headless chrome
        $browser = $browserFactory->createBrowser([
            'connectionDelay' => 0.5,
            'debugLogger'     => Storage::path('chrome.log'), // will enable verbose mode,
            'noSandbox' => true,
            'userAgent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:102.0) Gecko/20100101 Firefox/102.0',
        ]);

        $page = $browser->createPage();
        $page->setDownloadPath(Storage::path('/csv'));
        try {
            $page->navigate('https://cdn01.prnews.io/tmp/q1Hu45i0h45Jl10m74gH0J1vbMs/1Er5fe7_pro_all_platforms_ru.csv.zip?1658825309');
            sleep(10);
        } finally {
            $browser->close();
        }
    }

}

