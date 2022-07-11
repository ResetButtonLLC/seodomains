<?php

namespace App\Dto;

use App\Enums\Currency;
use App\Helpers\DomainsHelper;
use Carbon\Carbon;
use Illuminate\Support\Str;

class Domain
{

    protected string $name;
    protected int|null $price = null;
    protected int|null $traffic = null;
    protected Currency $currency;
    protected string $niches;
    protected int $stockId = 0;
    protected Carbon|null $created_at;
    protected Carbon|null $updated_at;

    public function __construct(string $domain)
    {
        $this->name = strtolower($domain);
        $this->currency = Currency::USD;
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

    public function setPrice(string|int $price, Currency $currency): void
    {
        //todo конверсия если гривны
        $this->currency = $currency;
        $this->price = DomainsHelper::getPriceFromString($price);
    }

    public function isDataOk() : bool
    {
        return ($this->isNameValid() && $this->price);
    }

    public function getTraffic(): ?int
    {
        return $this->traffic;
    }

    public function setTraffic(int|string $traffic): void
    {
        $this->traffic = DomainsHelper::getTrafficFromString($traffic);
    }

    public function getStockId(): int
    {
        return $this->stockId;
    }

    public function setStockId(int $stockId): void
    {
        $this->stockId = $stockId;
    }

    public function getNiches(): string
    {
        return $this->niches;
    }

    public function setNiches(array $niches): void
    {
        $this->niches = implode("; ", $niches);
    }

    public function compareCreatedAndUpdated() : bool
    {
        return $this->created_at == $this->updated_at;
    }



}