<?php

namespace Tests\Feature;

use App\Helpers\DomainsHelper;
use Tests\TestCase;

class DomainHelperTest extends TestCase
{
    public function testGetTrafficFromString()
    {
        $datasets = [
            ['                           1.2&nbsp;млн            ', 1200000],
            ['                        254.9&nbsp;тис.            ', 254900],
            ['                         713            ',713]
        ];

        foreach ($datasets as $dataset) {
            $this->assertEquals($dataset[1],DomainsHelper::getTrafficFromString($dataset[0]));
        }

    }
}
