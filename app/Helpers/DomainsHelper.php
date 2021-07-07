<?php
/**
 * Created by PhpStorm.
 * User: a.shatrov
 * Date: 21.01.2021
 * Time: 14:36
 */

namespace App\Helpers;

use Illuminate\Database\Eloquent\Collection;


class DomainsHelper
{
    public static function getIdByUrl(Collection $domains_db, string $domain)
    {
        $result = $domains_db->firstWhere('url', '=', $domain);
        return $result->id ?? null;
    }

    public static function getPriceFromString(string $price)
    {
        return intval(preg_replace('#[^0-9\.]#', '', $price)) ?? null;
    }
}