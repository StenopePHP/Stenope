<?php

/*
 * This file is part of the "StenopePHP/Stenope" bundle.
 *
 * @author Thomas Jarrand <thomas.jarrand@gmail.com>
 */

namespace Stenope\Bundle\Service;

use Stenope\Bundle\Behaviour\HtmlCrawlerManagerInterface;
use Stenope\Bundle\Content;
use Symfony\Component\DomCrawler\Crawler;

class SharedHtmlCrawlerManager implements HtmlCrawlerManagerInterface
{
    /**
     * @var array<string,array<string,Crawler>>
     */
    private array $crawlers = [];

    public function get(Content $content, array $data, string $property): ?Crawler
    {
        $key = "{$content->getType()}:{$content->getSlug()}";

        if (isset($this->crawlers[$key][$property])) {
            return $this->crawlers[$key][$property];
        }

        $crawler = $this->createCrawler($data[$property]);

        if (!$crawler) {
            return null;
        }

        if (!isset($this->crawlers[$key])) {
            $this->crawlers[$key] = [];
        }

        return $this->crawlers[$key][$property] = $crawler;
    }

    public function save(Content $content, array &$data, string $property): void
    {
        // Will be saved only once in saveAll.
    }

    public function saveAll(Content $content, array &$data): void
    {
        $key = "{$content->getType()}:{$content->getSlug()}";

        if (!isset($this->crawlers[$key])) {
            return;
        }

        foreach ($this->crawlers[$key] as $property => $crawler) {
            $data[$property] = $crawler->filterXPath('//body')->first()->html();
        }

        unset($this->crawlers[$key]);
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
