<?php

namespace Tests\Feature;

use App\Dto\Domain;
use App\Helpers\DomainsHelper;
use Tests\TestCase;

class DomainHelperTest extends TestCase
{

    /**
     * @dataProvider stringTrafficProvider
     */
    public function testGetTrafficFromString($trafficString,$trafficNumber)
    {
        $this->assertEquals(DomainsHelper::getTrafficFromString($trafficString),$trafficNumber);
    }

    /**
     * @dataProvider stringPriceProvider
     */
    public function testGetPriceFromString($trafficString,$trafficNumber)
    {
        $this->assertEquals(DomainsHelper::getPriceFromString($trafficString),$trafficNumber);
    }

    public function testNonValidZones() {
        $nonValidDomainZones = DomainsHelper::getNonvalidZones();
        foreach ($nonValidDomainZones as $nonValidDomainZone) {
            $domain = new Domain('putinhuylo'.$nonValidDomainZone);
            $this->assertFalse($domain->isNameValid());
        }
    }

    public function stringTrafficProvider() : array
    {
        return [
            ['                           1.2&nbsp;млн            ', 1200000],
            ['                        254.9&nbsp;тис.            ', 254900],
            ['                         713            ',713]
        ];
    }

    public function stringPriceProvider() : array
    {
        return [
            ['35 549,12₴', 35549.12],
        ];
    }

}
