<?php

namespace App\Services\Parsers;

use App\Dto\Domain;
use App\Dto\Domain as DomainDto;
use App\Dto\ParserProgressCounter;
use App\Enums\Currency;
use App\Exceptions\ParserException;
use App\Helpers\StorageHelper;
use App\Models\CollaboratorDomain;
use App\Models\PrnewsDomain;
use App\Models\StockDomain;
use App\Models\Update;
use HeadlessChromium\Page;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use HeadlessChromium\BrowserFactory;
use Illuminate\Http\File;
use Rap2hpoutre\FastExcel\FastExcel;

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
        $tries = 100;
        do {
            $csvLink = $this->getCsvLink();
            $tries--;
            if (!$tries) {
                throw new ParserException('No Chrome retries left while obtaining CSV link');
            }
        } while (!$csvLink);

        $this->downloadCSV($csvLink);

        $currencyRate = $this->getCurrencyRate();

        $csvDomains = (new FastExcel)->import($this->csvStorage->path($this->parserName.'.csv'));

        $this->counter = new ParserProgressCounter($csvDomains->count());

        foreach ($csvDomains as $row) {
            //Получаем данные домена
            $domainDto = $this->fetchDomainData($row);
            //todo пустое имя должно вызывать ошибку

            //Проверяем валидноcть спарсенных данных так как может быть "URL скрыт", нету цены и т.д.
            if ($domainDto->isDataOk()) {

                $domainDto->convertPrice($currencyRate);
                //Если с данными все ОК, то добавляем/обновляем домен
                dd($domainDto);
                $stockDomain = $this->upsertDomain($domainDto);

                //Сравниваем время создания и апдейта, Если время совпадает, то домен новый, если нет - то домен уже был.
                ($stockDomain->created_at == $stockDomain->updated_at) ? $this->counter->incNew() : $this->counter->incUpdated();
            } else {
                //Если данные невалидны, то обновляем счетчик пропущеных
                $this->counter->incSkipped();
            }

           Log::stack(['stderr', $this->logChannel])->info('Domain: '.$domainDto->getName().' | State: '.$this->counter->getLastAddedTo().' | Progress: '.$this->counter->getCurrent().'/'.$this->counter->getTotal().' | Added: '.$this->counter->getNew().' | Updated: '.$this->counter->getUpdated().' | Skipped:'. $this->counter->getSkipped());
        }


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

    protected function getCurrencyRate() : float
    {
        Log::stack(['stderr', $this->logChannel])->info('Parse currency rate');
        $currencyRate = null;
        $tries = 100;
        do {
            $tries--;
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
                $page->screenshot()->saveToFile($this->logStorage->path('1-Currency-LoginPage.jpg'));
                $page->mouse()->find('input[name=mail]')->click();
                $page->keyboard()->typeText(config('parsers.prnews.login'));
                $page->mouse()->find('input[name=password]')->click();
                $page->keyboard()->typeText(config('parsers.prnews.password'));
                $page->mouse()->find('input[type=submit]')->click();
                sleep(10);
                $page->screenshot()->saveToFile($this->logStorage->path('2-Currency-AfterLoginPage.jpg'));

                Log::stack(['stderr', $this->logChannel])->info('Navigate to settings');
                $page->navigate('https://prnews.io/account/settings/');
                sleep(10);
                $page->screenshot()->saveToFile($this->logStorage->path('3-Currency-Settings.jpg'));

                $currencyRateTag = $page->dom()->querySelector('input:read-only:last-child');
                $currencyRate = $currencyRateTag->getAttribute('value');
                Log::stack(['stderr', $this->logChannel])->info('Got currency rate => '.$currencyRate);

            } catch (\Exception $e) {
                Log::stack(['stderr', $this->logChannel])->error('Parsing Failed with error: '.$e->getMessage());
            }
            finally {
                $browser->close();
            }
        } while (!$currencyRate && $tries);

        if (!$tries) {
            throw new ParserException('No Chrome retries left while obtaining Currency Rate');
        }

        return $currencyRate * 1.01;
    }

}

