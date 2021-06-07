<?php

declare(strict_types=1);

/*
 * This file is part of the "StenopePHP/Stenope" bundle.
 *
 * @author Thomas Jarrand <thomas.jarrand@gmail.com>
 */

namespace Stenope\Bundle\Processor;

use Stenope\Bundle\Behaviour\HtmlCrawlerManagerInterface;
use Stenope\Bundle\Behaviour\ProcessorInterface;
use Stenope\Bundle\Content;
use Stenope\Bundle\TableOfContent\CrawlerTableOfContentGenerator;

/**
 * Build a table of content from the content titles if it exposes a tableOfContent value as true.
 * If the content exposes a tableOfContent value as int, it'll be used as the max depth for this specific content.
 */
class TableOfContentProcessor implements ProcessorInterface
{
    private CrawlerTableOfContentGenerator $generator;
    private HtmlCrawlerManagerInterface $crawlers;
    private string $tableOfContentProperty;
    private string $contentProperty;
    private int $minDepth;
    private int $maxDepth;

    public function __construct(
        CrawlerTableOfContentGenerator $generator,
        HtmlCrawlerManagerInterface $crawlers,
        string $tableOfContentProperty = 'tableOfContent',
        string $contentProperty = 'content',
        int $minDepth = 1,
        int $maxDepth = 6
    ) {
        $this->generator = $generator;
        $this->crawlers = $crawlers;
        $this->tableOfContentProperty = $tableOfContentProperty;
        $this->contentProperty = $contentProperty;
        $this->minDepth = $minDepth;
        $this->maxDepth = $maxDepth;
    }

    public function __invoke(array &$data, Content $content): void
    {
        if (!isset($data[$this->contentProperty], $data[$this->tableOfContentProperty])) {
            // Skip on unavailable content property or no TOC property configured
            return;
        }

        $tocValue = $data[$this->tableOfContentProperty];

        if (!\is_int($tocValue) && true !== $tocValue) {
            // if it's neither an int or true,
            // disable the TOC generation for this specific content and unset the value,
            // so denormalization can rely on a default value.
            unset($data[$this->tableOfContentProperty]);

            return;
        }

        $crawler = $this->crawlers->get($content, $data, $this->contentProperty);

        if (\is_null($crawler)) {
            return;
        }

        $data[$this->tableOfContentProperty] = $this->generator->getTableOfContent(
            $crawler,
            $this->minDepth,
            // Use the int value as max depth if specified, or fallback to default max depth otherwise:
            \is_int($tocValue) ? $tocValue : $this->maxDepth
        );
    }
}
