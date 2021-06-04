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
    private array $crawlers = [];

    public function get(Content $content, array &$data, string $property): ?Crawler
    {
        $key = "{$content->getType()}:{$content->getSlug()}";

        if (isset($this->crawlers[$key])) {
            if (isset($this->crawlers[$key][$property])) {
                return $this->crawlers[$key][$property];
            }
        }

        $crawler = $this->createCrawler($data[$property]);

        if ($crawler) {
            if (!isset($this->crawlers[$key])) {
                $this->crawlers[$key] = [];
            }

            $this->crawlers[$key][$property] = $crawler;

            return $crawler;
        }

        return null;
    }

    public function save(Content $content, array &$data, string $property, bool $force = false): void
    {
        $key = "{$content->getType()}:{$content->getSlug()}";

        if ($force && isset($this->crawlers[$key]) && isset($this->crawlers[$key][$property])) {
            $data[$property] = $this->crawlers[$key][$property]->html();
            unset($this->crawlers[$key][$property]);
        }
    }

    public function saveAll(Content $content, array &$data): void
    {
        $key = "{$content->getType()}:{$content->getSlug()}";

        if (!isset($this->crawlers[$key])) {
            return;
        }

        foreach ($this->crawlers[$key] as $property => $crawler) {
            $data[$property] = $crawler->html();
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
