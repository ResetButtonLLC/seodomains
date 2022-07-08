<?php

namespace App\Dto;

use App\Helpers\DomainsHelper;
use phpDocumentor\Reflection\Types\Integer;

abstract class Domain
{
    const CURRENCY_USD = 'usd';
    const CURRENCY_UAH = 'uah';

    protected string $name;
    protected int|null $price = null;
    protected string $currency = self::CURRENCY_USD;

    public function __construct(string $domain)
    {
        $this->name = $domain;
    }

    public function isNameValid() : bool
    {
        return str_contains($this->name,'.');
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