<?php

namespace App\Dto;

use App\Enums\Currency;
use App\Helpers\DomainsHelper;
use Carbon\Carbon;
use Illuminate\Support\Str;

class Domain
{

    protected string $name;
    protected float|null $price = null;
    protected int|null $traffic = null;
    protected Currency $currency;
    protected string $theme;
    protected int $stockId = 0;

    public function __construct(string $domain)
    {
        $this->setName($domain);
        $this->currency = Currency::UAH;
    }

    public function isNameValid() : bool
    {
        $domainValid = str_contains($this->name,'.');
        if (Str::contains($this->name,DomainsHelper::getNonvalidZones())) {
            $domainValid = false;
        };

        return $domainValid;
    }

    private function setName(string $domain) : void
    {
        $this->name = strtolower($domain);
        $this->name = preg_replace('/^www\./','',$this->name);
    }

    public function getName() : string
    {
        return $this->name;
    }

    public function getPrice(): float
    {
        return $this->price;
    }

    public function setPrice(float $price, Currency $currency): void
    {
        //todo конверсия в гривны, если доллар
        $this->currency = $currency;
        $this->price = $price;
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

    public function getTheme(): string
    {
        return $this->theme;
    }

    public function setTheme(string $theme): void
    {
        $this->theme = $theme;
    }

}