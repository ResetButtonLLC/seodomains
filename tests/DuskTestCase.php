<?php

namespace Tests;

use Facebook\WebDriver\Chrome\ChromeOptions;
use Facebook\WebDriver\Remote\RemoteWebDriver;
use Laravel\Dusk\TestCase as BaseTestCase;

abstract class DuskTestCase extends BaseTestCase
{
    use CreatesApplication;

    /**
     * Prepare for Dusk test execution.
     *
     * @beforeClass
     * @return void
     */
    public static function prepare()
    {
        static::startChromeDriver();
    }

    /**
     * Create the RemoteWebDriver instance.
     *
     * @return \Facebook\WebDriver\Remote\RemoteWebDriver
     */
    protected function driver()
    {
        $ua = 'Mozilla/5.0 (iPhone; CPU iPhone OS 9_1 like Mac OS X) AppleWebKit/601.1.46 (KHTML, like Gecko) Version/9.0 Mobile/13B143 Safari/601.1';
        $options = new ChromeOptions;
        $options->setExperimentalOption('mobileEmulation', ['userAgent' => $ua]);
        $options->addArguments([
            '--disable-gpu',
            '--headless',
            '--no-sandbox',
            '--proxy-server=p.webshare.io:9999'
        ]);

        return RemoteWebDriver::create('http://localhost:9515', $options->toCapabilities(),300000,300000);
    }
}
