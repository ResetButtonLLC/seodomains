<?php

namespace App\Dto;

class ParserCounter
{
    private int $total = 0;
    private int $new = 0;
    private int $updated = 0;

    public function getCurrent(): int
    {
        return $this->new + $this->updated;
    }

    public function getTotal(): int
    {
        return $this->total;
    }

    public function setTotal(int $total): void
    {
        $this->total = $total;
    }

    public function getNew(): int
    {
        return $this->new;
    }

    public function incNew(): void
    {
        $this->new++;
    }


    public function getUpdated(): int
    {
        return $this->updated;
    }

    public function incUpdated(): void
    {
        $this->updated++;
    }




}