<?php

/*
 * This file is part of the "StenopePHP/Stenope" bundle.
 *
 * @author Thomas Jarrand <thomas.jarrand@gmail.com>
 */

namespace Stenope\Bundle\Service;

use Stenope\Bundle\Behaviour\HtmlCrawlerManagerInterface;
use Symfony\Component\DomCrawler\Crawler;

class SharedHtmlCrawlerManager implements HtmlCrawlerManagerInterface
{
    private array $crawlers = [];

    public function get(array &$data, string $property): ?Crawler
    {
        if (isset($this->crawlers[$property])) {
            return $this->crawlers[$property];
        }

        $crawler = $this->createCrawler($data[$property]);

        if ($crawler) {
            $this->crawlers[$property] = $crawler;

            return $crawler;
        }

        return null;
    }

    public function save(array &$data, string $property, bool $force = false): void
    {
        if (isset($this->crawlers[$property]) && $force) {
            $data[$property] = $this->crawlers[$property]->html();
            unset($this->crawlers[$property]);
        }
    }

    public function saveAll(array &$data): void
    {
        foreach ($this->crawlers as $property => $crawler) {
            $data[$property] = $crawler->html();
        }

        $this->crawlers = [];
    }

    public function createCrawler(string $content): ?Crawler
    {
        $crawler = new Crawler($content);

        try {
            $crawler->html();
        } catch (\Exception $e) {
            // Content is not valid HTML.
            return null;
        }

        return $crawler;
    }
}
