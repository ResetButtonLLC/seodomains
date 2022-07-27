<?php

namespace App\Services\Parsers;

use App\Dto\Domain;
use App\Dto\ParserProgressCounter;
use App\Exceptions\ParserException;
use App\Helpers\StorageHelper;
use App\Models\StockDomain;
use App\Models\Update;
use HeadlessChromium\Page;
use http\Exception\RuntimeException;
use Illuminate\Support\Arr;
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
            'path' => storage_path('app/parsers/'.$this->parserName.'/logs/'.$this->parserName.'.log'),
        ]);

        Log::stack(['stderr', $this->logChannel])->info('Fire up parser '.$this->parserName);

        $this->logStorage = Storage::build([
            'driver' => 'local',
            'root' => storage_path('app/parsers/'.$this->parserName.'/logs/pages/'),
        ]);

        $this->csvStorage = Storage::build([
            'driver' => 'local',
            'root' => storage_path('app/parsers/'.$this->parserName.'/csv/'),
        ]);

    }

    public function parse() : void
    {
        //Бразуерная эмуляция может вылетать с ошибкой если при логине вылазит капча или прокси медленный, операцию нужно выполнять несколько раз
        /*
        $tries = 100;
        do {
            $csvLink = $this->getCsvLink();
            $tries--;
            if (!$tries) {
                throw new ParserException('No Chrome retries left while obtaining CSV link');
            }
        } while (!$csvLink);
        */

        $csvLink = 'https://cdn01.prnews.io/tmp/q1Hu45i0h45Jl10m74gH0J1vbMs/1Er5fe7_pro_all_platforms_ru.csv.zip?1658914238';

        $this->downloadCSV($csvLink);
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
 //           'debugLogger'     => 'php://stdout', // will enable verbose mode,
            'noSandbox' => true,
            'userAgent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:102.0) Gecko/20100101 Firefox/102.0',
            'proxyServer' => $proxy
        ]);

        try {
            // creates a new page and navigate to an URL
            $page = $browser->createPage();
            Log::stack(['stderr', $this->logChannel])->info('Logging in');
            $page->navigate('https://prnews.io/login/')->waitForNavigation();
            $page->screenshot()->saveToFile($this->logStorage->path('1-LoginPage.jpg'));
            $page->mouse()->find('input[name=mail]')->click();
            $page->keyboard()->typeText(config('parsers.prnews.login'));
            $page->mouse()->find('input[name=password]')->click();
            $page->keyboard()->typeText(config('parsers.prnews.password'));
            $page->mouse()->find('input[type=submit]')->click();
            sleep(10);
            $page->screenshot()->saveToFile($this->logStorage->path('2-AfterLoginPage.jpg'));

            Log::stack(['stderr', $this->logChannel])->info('Navigate to catalog');
            $page->navigate('https://prnews.io/ru/sites/');
            sleep(10);
            $page->screenshot()->saveToFile($this->logStorage->path('3-SiteListPage.jpg'));

            Log::stack(['stderr', $this->logChannel])->info('Click download link');
            $page->mouse()->find('#pro_export')->click();
            $downloadTag = $page->dom()->querySelector('#pro_export_success_full a');
            $csvLink = $downloadTag->getAttribute('href');
            Log::stack(['stderr', $this->logChannel])->info('Got link for CSV => '.$csvLink);

        } catch (\Exception $e) {
            Log::stack(['stderr', $this->logChannel])->error('Parsing Failed with error: '.$e->getMessage());
            $csvLink = '';
        }
        finally {
            $browser->close();
        }

        return $csvLink;

    }

    protected function downloadCSV(string $csvLink) : void
    {

        //Delete all zip files
        StorageHelper::deleteFilesWithExtension($this->csvStorage->path(''),'zip');

        //Download new file
        Log::stack(['stderr', $this->logChannel])->info('Downloading File => '.$csvLink);
        $browserFactory = new BrowserFactory('chromium');

        $browser = $browserFactory->createBrowser([
            'connectionDelay' => 0.5,
            'debugLogger'     => Storage::path('chrome.log'), // will enable verbose mode,
            'noSandbox' => true,
            'userAgent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:102.0) Gecko/20100101 Firefox/102.0',
        ]);

        $page = $browser->createPage();
        $page->setDownloadPath($this->csvStorage->path(''));
        try {
            $page->navigate($csvLink);
            sleep(10);
        } finally {
            $browser->close();
        }

        //Extract CSV and replace old one
        Log::stack(['stderr', $this->logChannel])->info('Replace old CSV with new');
        $csvZip = StorageHelper::getFirstFileWithExtension($this->csvStorage->path(''),'zip');
        StorageHelper::extractZipToCsv($csvZip, $this->parserName);
    }

}

