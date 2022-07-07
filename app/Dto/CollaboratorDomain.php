<?php

namespace App\Dto;

use App\Helpers\DomainsHelper;

class CollaboratorDomain extends Domain
{
    protected string $currency = self::CURRENCY_UAH;
    protected int|null $traffic = null;
    protected string $niches;
    protected int $id = 0;

    public function getTraffic(): ?int
    {
        return $this->traffic;
    }

    public function setTraffic(int|string $traffic): void
    {
        $this->traffic = DomainsHelper::getTrafficFromString($traffic);
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function setId(int $id): void
    {
        $this->id = $id;
    }

    public function getNiches(): string
    {
        return $this->niches;
    }


    public function setNiches(array $niches): void
    {
        $this->niches = implode("; ", $niches);
    }




}