<?php

namespace App\Extensions\Symfony\DomCrawler;

class Crawler extends \Symfony\Component\DomCrawler\Crawler
{
    public function fetchOptionalText(string $cssSelector) : ?string
    {
        if ($this->filter($cssSelector)->count() > 0) {
            return trim($this->filter($cssSelector)->first()->text());
        } else {
            return null;
        }
    }
}