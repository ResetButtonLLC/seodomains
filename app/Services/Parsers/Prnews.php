<?php

namespace App\Services\Parsers;

use App\Dto\Domain;
use App\Dto\Domain as DomainDto;
use App\Enums\Currency;
use App\Exceptions\ParserException;
use App\Helpers\StorageHelper;
use App\Models\PrnewsDomain;
use App\Models\StockDomain;
use Illuminate\Support\Facades\Log;
use HeadlessChromium\BrowserFactory;
use HeadlessChromium\Browser\ProcessAwareBrowser;
use HeadlessChromium\Page;

class Prnews extends CsvParser
{

    //Функция получения курса валют переопределена, так как валюта - USD
    protected function setCurrencyRate() : void
    {
        Log::stack(['stderr', $this->logChannel])->info('Parse currency rate');
        //Браузерная эмуляция может вылететь с ошибкой из за капчи, поэтому ставим 100 попыток
        $tries = 100;
        do {
            $tries--;
            $currencyRate = $this->getCurrencyRateViaChrome();
            if (!$tries) {
                throw new ParserException('No Chrome retries left while obtaining Currency Rate');
            }
        } while (!$currencyRate);

        $this->currencyRate = $currencyRate * 1.01;
    }

    protected function getCurrencyRateViaChrome() : float
    {
        $currencyRate = 0;

        $browser = $this->initChrome();
        $page = $this->prnewsLogin($browser, 'getCurrencyRate');

        try {
            Log::stack(['stderr', $this->logChannel])->info('Navigate to settings');
            $page->navigate('https://prnews.io/account/settings/');
            sleep(10);
            $page->screenshot()->saveToFile($this->logStorage->path('getCurrencyRate-3-Settings.jpg'));

            $currencyRateTag = $page->dom()->querySelector('input:read-only:last-child');
            $currencyRate = $currencyRateTag->getAttribute('value');
            Log::stack(['stderr', $this->logChannel])->info('Got currency rate => '.$currencyRate);

        } catch (\Exception $e) {
            Log::stack(['stderr', $this->logChannel])->error('Parsing Failed with error: '.$e->getMessage());
        }
        finally {
            $browser->close();
        }

        //При ошибке парсинга значение будет 0;
        $currencyRate = floatval($currencyRate);

        return $currencyRate;
    }

    protected function downloadCSV() : void
    {
        $csvLink = $this->getCsvLink();
        $this->downloadCSVViaChrome($csvLink);
    }


    private function getCsvLink() : string
    {
        Log::stack(['stderr', $this->logChannel])->info('Get CVS file link');
        //Бразуерная эмуляция может вылетать с ошибкой если при логине вылазит капча или прокси медленный, операцию нужно выполнять несколько раз
        $tries = 100;
        do {
            $csvLink = $this->getCsvLinkViaChrome();
            //todo CSV link validation
            $tries--;
            if (!$tries) {
                throw new ParserException('No Chrome retries left while obtaining CSV link');
            }
        } while (!$csvLink);

        return $csvLink;
    }

    //Логинимся в аккаунт и скачиваем CSV
    private function getCsvLinkViaChrome() : string
    {
        $browser = $this->initChrome();
        $page = $this->prnewsLogin($browser, 'getCSV');

        try {
            Log::stack(['stderr', $this->logChannel])->info('Navigate to catalog');
            $page->navigate('https://prnews.io/ru/sites/');
            sleep(10);
            $page->screenshot()->saveToFile($this->logStorage->path('getCSV-3-SiteListPage.jpg'));

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

    protected function downloadCSVViaChrome(string $csvLink) : void
    {

        //Delete all zip files
        StorageHelper::deleteFilesWithExtension($this->csvStorage->path(''),'zip');

        //Download new file
        Log::stack(['stderr', $this->logChannel])->info('Downloading File => '.$csvLink);

        $browser = $this->initChrome(false);

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

    protected function fetchDomainData(array $row) : Domain
    {
        $domain = new DomainDto(data_get($row,"Url"));
        $domain->setStockId(data_get($row,"ID"));
        $domain->setPrice(data_get($row,"Cost (USD)"),Currency::USD);
        $domain->setCountry(data_get($row,"Country"));

        data_get($row,"SimilarWeb Estimated Visits") ? $domain->setTraffic(data_get($row,"SimilarWeb Estimated Visits")) : null ;
        $domain->setTheme(data_get($row,"Category"));
        (data_get($row,"Majestic Citation Flow") != "-") ? $domain->setCf(data_get($row,"Majestic Citation Flow")) : null ;
        (data_get($row,"Majestic Trust Flow") != "-") ? $domain->setTf(data_get($row,"Majestic Trust Flow")) : null ;
        (data_get($row,"Ahrefs Domain Rating") != "-") ? $domain->setDr(data_get($row,"Ahrefs Domain Rating")) : null ;

        return $domain;
    }

    protected function upsertDomain(DomainDto $domainDto) : StockDomain
    {
        $domain = \App\Models\Domain::updateOrCreate(
            ['domain' => $domainDto->getName()],
            ['domain' => $domainDto->getName()]
        );

        $prnewsDomain = PrnewsDomain::updateOrCreate(
            [
                'id' => $domainDto->getStockId()
            ],
            [
                'domain_id' => $domain->id,
                'domain' => $domainDto->getName(),
                'price' => $domainDto->getPrice(),
                'country' => $domainDto->getCountry(),
                'theme' => $domainDto->getTheme(),
                'traffic' => $domainDto->getTraffic(),
                'dr' => $domainDto->getDr(),
                'tf' => $domainDto->getTf(),
                'cf' => $domainDto->getCf(),
                'updated_at' => now()
            ]
        );

        return $prnewsDomain;

    }

    private function initChrome(bool $withProxy = true) : ProcessAwareBrowser
    {

        Log::stack(['stderr', $this->logChannel])->info('Initializing chrome');

        $browserSettings = [
            'connectionDelay' => 0.5,
            //           'debugLogger'     => 'php://stdout', // will enable verbose mode,
            'noSandbox' => true,
            'userAgent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:102.0) Gecko/20100101 Firefox/102.0',

        ];

        if ($withProxy) {
            $proxy = 'p.webshare.io:'.rand(10000,11000);
            $browserSettings['proxyServer'] = $proxy;
            Log::stack(['stderr', $this->logChannel])->info('Bind proxy '.$proxy);
        }

        $browserFactory = new BrowserFactory('chromium');

        // starts headless chrome
        $browser = $browserFactory->createBrowser($browserSettings);

        return $browser;
    }

    private function prnewsLogin(ProcessAwareBrowser $browser, string $stage) : Page
    {
        try {
            // creates a new page and navigate to an URL
            $page = $browser->createPage();
            Log::stack(['stderr', $this->logChannel])->info('Logging in');
            $page->navigate('https://prnews.io/login/')->waitForNavigation();
            $page->screenshot()->saveToFile($this->logStorage->path($stage.'-1-LoginPage.jpg'));
            $page->mouse()->find('input[name=mail]')->click();
            $page->keyboard()->typeText(config('parsers.prnews.login'));
            $page->mouse()->find('input[name=password]')->click();
            $page->keyboard()->typeText(config('parsers.prnews.password'));
            $page->mouse()->find('input[type=submit]')->click();
            sleep(10);
            $page->screenshot()->saveToFile($this->logStorage->path($stage.'-2-AfterLoginPage.jpg'));
        } catch (\Exception $e) {
            Log::stack(['stderr', $this->logChannel])->error('Parsing Failed with error: '.$e->getMessage());
            $browser->close();
        }

        return $page;

    }

    protected function postUpdateActions(): void
    {
        PrnewsDomain::query()->whereDate('updated_at', '<=', now()->subDays(1)->toDateTimeString())->delete();
    }

    //Так как парсер использует Хром, а не HTTP клиент, функция является заглушкой
    protected function checkCookie(): void
    {

    }

}

