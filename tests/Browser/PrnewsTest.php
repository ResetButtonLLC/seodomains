<?php

namespace Tests\Browser;

use App\Models\{Domains, Prnews};
use App\Helpers\DomainsHelper;

use Facebook\WebDriver\WebDriverBy;

use Tests\DuskTestCase;
use Laravel\Dusk\Browser;

class PrnewsTest extends DuskTestCase
{
    /**
     * Init browser.
     *
     * @var Browser
     */
    private $browser;

    /**
     * Prnews home url.
     *
     * @var string
     */
    private const HOME_URL = 'https://prnews.io';

    /**
     * Init counters.
     *
     * @var int[]
     */
    private $counters = [
        'countPages' => 0,
        'currentPage' => 2,
        'newDomains' => 0,
        'updatedDomains' => 0
    ];

    /**
     * Get domains from storage.
     *
     * @var
     */
    private $domains;

    /**
     * A Dusk test example.
     *
     * @return void
     */
    public function testGetDomains()
    {
        $this->browse(function (Browser $browser) {
            $this->browser = $browser;
            $this->domains = Domains::all('id', 'url');

            $this->setCookie();
            $this->setTotalPages();

            while ($this->counters['currentPage'] < $this->counters['countPages']) {
                $cards = $this->getCards($this->counters['currentPage']);

                foreach ($cards as $card) {
                    $this->findOrUpdateDomain($card);
                }

                $this->counters['currentPage']++;
            }
        });
    }

    /**
     * Get cards from page id.
     *
     * @param int $pageId
     * @return array
     */
    private function getCards(int $pageId) : array
    {
        return $this->browser
            ->visit($this::HOME_URL . '/sites/page/' . $pageId . '/perpage/96/')
            ->pause(1000)
            ->screenshot('prnews-page-' . $pageId)
            ->elements('.cards > div > a');
    }

    /**
     * Find and update domain.
     *
     * @param $card
     */
    private function findOrUpdateDomain($card) : void
    {
        $cardName = $card->findElement(WebDriverBy::className('card_name'))->getText();
        $cardPrice = $card->findElement(WebDriverBy::className('card_price'))->getText();
        $cardAudience = $card->findElement(WebDriverBy::className('card_audience'))->getText();

        if (!$domainId = DomainsHelper::getIdByUrl($this->domains, $cardName)) {
            $domainId = Domains::insertGetId(['url' => mb_strtolower($cardName), 'created_at' => date('Y-m-d H:i:s')]);
        }

        $domain = Prnews::updateOrCreate(
            ['domain_id' => $domainId],
            ['url' => mb_strtolower($cardName), 'price' => DomainsHelper::getPriceFromString($cardPrice), 'audience' => $cardAudience]
        );

        if($domain->updated_at == $domain->created_at) {
            $this->counters['newDomains']++;
        } else {
            $this->counters['updatedDomains']++;
        }
    }

    /**
     * Set cookie to prnews.
     *
     * @return void
     */
    private function setCookie() : void
    {
        $this->browser
            ->visit($this::HOME_URL)
            ->plainCookie('remember_web_59ba36addc2b2f9401580f014c7f58ea4e30989d', 'eyJpdiI6IkYxTVBzd01uYTlLdUNhYzVZZzJmV1E9PSIsInZhbHVlIjoiWVo1VXpoRlo3S1NweDVtZ3NTbEhjekZpeThTOXZOMC83Y2FlMnI2UGdGR0orSnR3OVNpNzRyb0dGV1ltRkJRb2EyZ3FuMi9EQXd3a3I2MElsY1Y0VEluRVYzajkyVzFHY1hLaEhsU3hRM3BMUjVCWHdSWVV2N05LOE9lVFROenJEUUZiWW9iTzloVHRGSVZ2OUxXUnd6dVBKVjJERkNzK3E2ZkpocWJCUW9tenE4emFCWnc0MGUxK0dEV0F5UGJSREY0SEw1WW5hTTNrbjNqNHBTTld4UT09IiwibWFjIjoiYmU5MjM4ZDk1ZTQzOTQwMGM5NjUyNmNlZDg5Nzk4MzU5M2RjZjljZDUyZmU1ZWFiNmZhMGU3OTEwMDQyYjc5OSIsInRhZyI6IiJ9');

        $this->browser
            ->pause(1000);
    }

    /**
     * Get and set total pages from prnews.
     *
     * @return void
     */
    private function setTotalPages() : void
    {
        $this->counters['countPages'] = intval(
            $this->browser
                ->visit($this::HOME_URL . '/sites/perpage/96/')
                ->pause(1000)
                ->element('.pagination > li:nth-child(7) a')
                ->getText()
        );
    }

    /**
     * Registration account.
     *
     * @return void
     */
    private function registrationAccount() : void
    {
        $this->browser->visit($this::HOME_URL . '/signup')
            ->value('input#name', 'Василий Василиевич')
            ->value('input#phone', rand(1111111, 9999999))
            ->value('input#mail', 'vasiliy' . rand(1111111, 9999999) . '@gmail.com')
            ->value('input#pass', rand(1111111, 9999999))
            ->check('gdpr')
            ->press('Sign up')
            ->pause(1000)
            ->screenshot('prnews-registration')
            ->visit($this::HOME_URL . '/account')
            ->assertSee('Dashboard');
    }
}
