<?php

namespace Tests\Browser;

use Facebook\WebDriver\WebDriverBy;

use Tests\DuskTestCase;
use Laravel\Dusk\Browser;

class PrnewsLoginTest extends DuskTestCase
{
    /**
     * Prnews home url.
     *
     * @var string
     */
    private const homeUrl = 'https://prnews.io';

    /**
     * A Dusk test example.
     *
     * @return void
     */
    public function testExample()
    {
        $this->browse(function (Browser $browser) {
            $this->setCookie($browser);

            $counters = [
                'countPages' => $this->getCountPages($browser),
                'currentPage' => 2,
            ];

            while ($counters['currentPage'] < $counters['countPages']) {
                $getCards = $browser->visit($this::homeUrl . '/sites/page/' . $counters['currentPage'] . '/perpage/96/')->pause(1000)->screenshot('prnews-page-' . $counters['currentPage'])->elements('.cards > div > a');

                foreach ($getCards as $pageCard) {
                    $cardName = $pageCard->findElement(WebDriverBy::className('card_name'))->getText();
                    $cardPrice = $pageCard->findElement(WebDriverBy::className('card_price'))->getText();
                    $cardAudience = $pageCard->findElement(WebDriverBy::className('card_audience'))->getText();

                    dd($cardName, $cardPrice, $cardAudience);
                }

                $counters['currentPage']++;
            }
        });
    }

    /**
     * Set cookie to prnews.
     *
     * @param Browser $browser
     * @return void
     */
    private function setCookie(Browser $browser) : void
    {
        $browser->visit($this::homeUrl)->plainCookie('remember_web_59ba36addc2b2f9401580f014c7f58ea4e30989d', 'eyJpdiI6IkYxTVBzd01uYTlLdUNhYzVZZzJmV1E9PSIsInZhbHVlIjoiWVo1VXpoRlo3S1NweDVtZ3NTbEhjekZpeThTOXZOMC83Y2FlMnI2UGdGR0orSnR3OVNpNzRyb0dGV1ltRkJRb2EyZ3FuMi9EQXd3a3I2MElsY1Y0VEluRVYzajkyVzFHY1hLaEhsU3hRM3BMUjVCWHdSWVV2N05LOE9lVFROenJEUUZiWW9iTzloVHRGSVZ2OUxXUnd6dVBKVjJERkNzK3E2ZkpocWJCUW9tenE4emFCWnc0MGUxK0dEV0F5UGJSREY0SEw1WW5hTTNrbjNqNHBTTld4UT09IiwibWFjIjoiYmU5MjM4ZDk1ZTQzOTQwMGM5NjUyNmNlZDg5Nzk4MzU5M2RjZjljZDUyZmU1ZWFiNmZhMGU3OTEwMDQyYjc5OSIsInRhZyI6IiJ9')->pause(1000);
    }

    /**
     * Get count pages from prnews.
     *
     * @param Browser $browser
     * @return int
     */
    private function getCountPages(Browser $browser) : int
    {
        return intval($browser->visit($this::homeUrl . '/sites/perpage/96/')->pause(1000)->element('.pagination > li:nth-child(7) a')->getText());
    }

    /**
     * Registration account.
     *
     * @param $browser
     */
    private function registrationAccount($browser)
    {
        $browser->visit($this::homeUrl . '/signup')
            ->value('input#name', 'Василий Василиевич')
            ->value('input#phone', rand(1111111, 9999999))
            ->value('input#mail', 'vasiliy' . rand(1111111, 9999999) . '@gmail.com')
            ->value('input#pass', rand(1111111, 9999999))
            ->check('gdpr')
            ->press('Sign up')
            ->pause(1000)
            ->screenshot('prnews-registration')
            ->visit($this::homeUrl . '/account')
            ->assertSee('Dashboard');
    }
}
