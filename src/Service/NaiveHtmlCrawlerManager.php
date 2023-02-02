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

class NaiveHtmlCrawlerManager implements HtmlCrawlerManagerInterface
{
    /**
     * @var array<string,array<string,Crawler>>
     */
    private array $crawlers = [];

    public function get(Content $content, array $data, string $property): ?Crawler
    {
        $key = "{$content->getType()}:{$content->getSlug()}";
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
        $key = "{$content->getType()}:{$content->getSlug()}";

        if (isset($this->crawlers[$key][$property])) {
            $data[$property] = $this->crawlers[$key][$property]->filterXPath('//body')->first()->html();
            unset($this->crawlers[$key][$property]);
        }
    }

    public function saveAll(Content $content, array &$data): void
    {
        foreach ($this->crawlers as $crawlers) {
            foreach ($crawlers as $property => $crawler) {
                $data[$property] = $crawler->filterXPath('//body')->first()->html();
            }
        }

        $this->crawlers = [];
    }

    public function createCrawler(string $html): ?Crawler
    {
        $crawler = new Crawler($html);

        try {
            $crawler->html();
        } catch (\Exception $e) {
            // Content is not valid HTML.
            return null;
        }

        return $crawler;
    }
}
