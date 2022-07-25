<?php

namespace App\Services\Parsers;

use App\Dto\Domain;
use App\Dto\ParserProgressCounter;
use App\Models\StockDomain;
use App\Models\Update;
use HeadlessChromium\Page;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use HeadlessChromium\BrowserFactory;

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

        $this->storage = Storage::build([
            'driver' => 'local',
            'root' => storage_path('logs/parsers/'.$this->parserName.'/pages'),
        ]);
    }

    public function parse() : void
    {
        $this->getCsv();


    }

    //Логинимся в аккаунт и скачиваем CSV
    private function getCsv() : void
    {
        $browserFactory = new BrowserFactory('chromium');

        // starts headless chrome
        $browser = $browserFactory->createBrowser([
            'debugLogger'     => 'php://stdout', // will enable verbose mode,
            'noSandbox' => true,
            'userAgent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:102.0) Gecko/20100101 Firefox/102.0',
            'proxyServer' => 'p.webshare.io:10000'
        ]);

        try {
            // creates a new page and navigate to an URL
            $page = $browser->createPage();
            $page->navigate('https://prnews.io/login/')->waitForNavigation();
            $page->screenshot()->saveToFile(\Illuminate\Support\Facades\Storage::path('login.jpg'));

            $page->mouse()->find('input[name=mail]')->click();
            $page->keyboard()->typeText('soft@promodo.com');
            $page->mouse()->find('input[name=password]')->click();
            $page->keyboard()->typeText('H4WZAxJPkh94HbcPIOHB');
            $page->mouse()->find('input[type=submit]')->click();
            sleep(3);
            $page->screenshot()->saveToFile(\Illuminate\Support\Facades\Storage::path('afterLoginClick.jpg'));
            sleep(10);

            $page->navigate('https://prnews.io/ru/sites/')->waitForNavigation();
            sleep(3);
            $page->screenshot()->saveToFile(\Illuminate\Support\Facades\Storage::path('sitelist.jpg'));

            $page->mouse()->find('#pro_export')->click();

            $downloadTag = $page->dom()->querySelector('#pro_export_success_full a');
            $csvLink = $downloadTag->getAttribute('href');
            //dd($csvLink);

            $page->setDownloadPath(Storage::path('csv'));
            $page->mouse()->find('#pro_export_success_full a')->click();
            $page->navigate($csvLink)->waitForNavigation('networkidle2');
            dd($csvLink);

            // screenshot - Say "Cheese"! 😄
            $page->screenshot()->saveToFile(\Illuminate\Support\Facades\Storage::path('lk.jpg'));

            // pdf
            //$page->pdf(['printBackground' => false])->saveToFile('/foo/bar.pdf');
        } finally {
            // bye
            $browser->close();
        }
    }



}

