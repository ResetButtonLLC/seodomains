<?php

namespace App\Dto;

use App\Helpers\DomainsHelper;
use phpDocumentor\Reflection\Types\Integer;

abstract class Domain
{
    const CURRENCY_USD = 'usd';
    const CURRENCY_UAH = 'uah';

    protected string $domain;
    protected int $price;
    protected string $currency = self::CURRENCY_USD;

    public function __construct(string $domain)
    {
        $this->domain = $domain;
    }

    public function getDomain() : string
    {
        return $this->domain;
    }

    public function getPrice(): int
    {
        return $this->price;
    }

    public function setPrice(string|int $price): void
    {
        //todo конверсия если гривны
        $this->price = DomainsHelper::getPriceFromString($price);
    }



}