<?php
/**
 * Created by PhpStorm.
 * User: a.shatrov
 * Date: 21.01.2021
 * Time: 14:36
 */

namespace App\Helpers;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Str;


class DomainsHelper
{

    public static function getNonvalidZones() : array
    {
        $nonValidZones = [
            'ru',
            '.рф',
            '.xn--p1ai',
            '.рус',
            'xn--p1acf',
            'su',
            'moscow',
            'москва',
            'xn--80adxhks',
            'tatar'
        ];

        $nonValidZones = array_map(function ($zone) {
            return '.'.$zone;
            }, $nonValidZones);

        return $nonValidZones;
    }

    public static function getIdByUrl(Collection $domains_db, string $domain)
    {
        $result = $domains_db->firstWhere('url', '=', $domain);
        return $result->id ?? null;
    }

    public static function getPriceFromString(string $price)
    {
        return floatval(preg_replace('#[^0-9\.]#', '', $price)) ?? null;
    }

    public static function getTrafficFromString(string $traffic)
    {
        $multiplier = 1;
        Str::contains($traffic,'млн') ? $multiplier = 1000000 : null;
        Str::contains($traffic,'тис') ? $multiplier = 1000 : null;

        $trafficValue = intval(floatval($traffic) * $multiplier);

        return $trafficValue;
    }

}