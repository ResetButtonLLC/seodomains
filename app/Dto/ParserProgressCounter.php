<?php

namespace App\Dto;

class ParserProgressCounter
{
    private int $total;
    private int $new = 0;
    private int $updated = 0;
    private int $skipped = 0;
    private string $lastAddedTo = '';

    public function __construct(int $total = 0)
    {
        $this->total = $total;
    }

    public function getCurrent(): int
    {
        return $this->new + $this->updated + $this->skipped;
    }

    public function getTotal(): int
    {
        return $this->total;
    }

    public function getNew(): int
    {
        return $this->new;
    }

    public function incNew(): void
    {
        $this->new++;
        $this->lastAddedTo = 'new';
    }

    public function getUpdated(): int
    {
        return $this->updated;
    }

    public function incUpdated(): void
    {
        $this->updated++;
        $this->lastAddedTo = 'updated';
    }

    public function getSkipped(): int
    {
        return $this->skipped;
    }

    public function incSkipped() : void
    {
        $this->skipped++;
        $this->lastAddedTo = 'skipped';
    }

    public function getLastAddedTo() : string
    {
        return $this->lastAddedTo;
    }





}