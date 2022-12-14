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
    protected int|null $dr = null;
    protected int|null $cf = null;
    protected int|null $tf = null;
    protected Currency $currency;
    protected string $theme = '';
    protected string $languages = '';
    protected int $stockId = 0;
    protected int $id = 0;
    protected string|null $country;
    protected bool $isPublicationTypeValid = true; //Определяет подходит ли домен для публикации типа контента, например в Prnews валидны только домены с типом "статья"

    public function __construct(string $domain)
    {
        $this->setName($domain);
        $this->currency = Currency::UAH;
    }

    public function isNameValid() : bool
    {
        $domainValid = str_contains($this->name,'.');

        if (Str::endsWith($this->name,DomainsHelper::getNonvalidZones())) {
            $domainValid = false;
        };

        if (Str::contains($this->name,'*')) {
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
        return ($this->isNameValid() && $this->price && $this->isPublicationTypeValid);
    }

    public function getTraffic(): ?int
    {
        return $this->traffic;
    }

    public function setTraffic(null|int|string $traffic): void
    {
        if ($traffic) {
            $this->traffic = DomainsHelper::getTrafficFromString($traffic);
        }
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

    public function getDr(): ?int
    {
        return $this->dr;
    }

    public function setDr(int $dr): void
    {
        $this->dr = $dr;
    }


    public function getCf(): ?int
    {
        return $this->cf;
    }

    public function setCf(null|int $cf): void
    {
        if ($cf) {
            $this->cf = $cf;
        }
    }

    public function getTf(): ?int
    {
        return $this->tf;
    }

    public function setTf(null|int $tf): void
    {
        if ($tf) {
            $this->tf = $tf;
        }
    }

    public function getCountry(): ?string
    {
        return $this->country;
    }

    public function setCountry(?string $country): void
    {
        $this->country = $country;
    }

    public function setId(int $id)
    {
        $this->id = $id;
    }

    public function getId() : string
    {
        return $this->id;
    }

    public function convertPrice(float $rate) : void
    {
        if ($this->currency->name != Currency::UAH) {
            $this->setPrice(round($this->price * $rate,2), Currency::UAH);
        }
    }

    public function isIsPublicationTypeValid(bool $isPublicationTypeValid) : void
    {
        $this->isPublicationTypeValid = $isPublicationTypeValid;
    }



}