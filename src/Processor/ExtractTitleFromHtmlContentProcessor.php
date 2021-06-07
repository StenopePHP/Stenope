<?php

/*
 * This file is part of the "StenopePHP/Stenope" bundle.
 *
 * @author Thomas Jarrand <thomas.jarrand@gmail.com>
 */

namespace Stenope\Bundle\Processor;

use Stenope\Bundle\Behaviour\HtmlCrawlerManagerInterface;
use Stenope\Bundle\Behaviour\ProcessorInterface;
use Stenope\Bundle\Content;

/**
 * Extract a content title from a HTML property by using the first available h1 tag.
 */
class ExtractTitleFromHtmlContentProcessor implements ProcessorInterface
{
    private HtmlCrawlerManagerInterface $crawlers;
    private string $contentProperty;
    private string $titleProperty;

    public function __construct(
        HtmlCrawlerManagerInterface $crawlers,
        string $contentProperty = 'content',
        string $titleProperty = 'title'
    ) {
        $this->crawlers = $crawlers;
        $this->contentProperty = $contentProperty;
        $this->titleProperty = $titleProperty;
    }

    public function __invoke(array &$data, Content $content): void
    {
        // Ignore if no content available, or if the title property is already set:
        if (!\is_string($data[$this->contentProperty] ?? null) || isset($data[$this->titleProperty])) {
            return;
        }

        $crawler = $this->crawlers->get($content, $data, $this->contentProperty);

        if (!$crawler) {
            return;
        }

        // Use the first h1 text as title:
        if (($title = $crawler->filter('h1')->first())->count() > 0) {
            $data[$this->titleProperty] = $title->text();
        }
    }
}
