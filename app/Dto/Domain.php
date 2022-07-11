<?php

namespace App\Dto;

use App\Helpers\DomainsHelper;
use Illuminate\Support\Str;
use phpDocumentor\Reflection\Types\Integer;

class Domain
{
    const CURRENCY_USD = 'usd';
    const CURRENCY_UAH = 'uah';

    protected string $name;
    protected int|null $price = null;
    protected string $currency = self::CURRENCY_USD;

    public function __construct(string $domain)
    {
        $this->name = strtolower($domain);
    }

    public function isNameValid() : bool
    {
        $domainValid = str_contains($this->name,'.');
        if (Str::contains($this->name,DomainsHelper::getNonvalidZones())) {
            $domainValid = false;
        };

        return $domainValid;
    }

    public function getName() : string
    {
        return $this->name;
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

    public function isDataOk() : bool
    {
        return ($this->isNameValid() && $this->price);
    }



}