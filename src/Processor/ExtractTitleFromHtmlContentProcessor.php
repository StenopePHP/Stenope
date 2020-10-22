<?php

/*
 * This file is part of the "StenopePHP/Stenope" bundle.
 *
 * @author Thomas Jarrand <thomas.jarrand@gmail.com>
 */

namespace Stenope\Processor;

use Stenope\Behaviour\ProcessorInterface;
use Stenope\Content;
use Symfony\Component\DomCrawler\Crawler;

/**
 * Extract a content title from a HTML property by using the first available h1 tag.
 */
class ExtractTitleFromHtmlContentProcessor implements ProcessorInterface
{
    private string $contentProperty;
    private string $titleProperty;

    public function __construct(string $contentProperty = 'content', string $titleProperty = 'title')
    {
        $this->contentProperty = $contentProperty;
        $this->titleProperty = $titleProperty;
    }

    public function __invoke(array &$data, string $type, Content $content): void
    {
        // Ignore if no content available, or if the title property is already set:
        if (!\is_string($data[$this->contentProperty] ?? null) || isset($data[$this->titleProperty])) {
            return;
        }

        $crawler = new Crawler($data[$this->contentProperty]);

        try {
            $crawler->html();
        } catch (\Exception $e) {
            // Content is not valid HTML.
            return;
        }

        $crawler = new Crawler($data[$this->contentProperty]);

        // Use the first h1 text as title:
        if (($title = $crawler->filter('h1')->first())->count() > 0) {
            $data[$this->titleProperty] = $title->text();
        }
    }
}
