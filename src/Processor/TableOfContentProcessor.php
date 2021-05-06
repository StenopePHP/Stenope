<?php

declare(strict_types=1);

/*
 * This file is part of the "StenopePHP/Stenope" bundle.
 *
 * @author Thomas Jarrand <thomas.jarrand@gmail.com>
 */

namespace Stenope\Bundle\Processor;

use Stenope\Bundle\Behaviour\ProcessorInterface;
use Stenope\Bundle\Content;
use Stenope\Bundle\TableOfContent\TableOfContentGenerator;
use Symfony\Component\DomCrawler\Crawler;

/**
 * Build a table of content from the content titles
 */
class TableOfContentProcessor implements ProcessorInterface
{
    private string $tableOfContentProperty;
    private string $contentProperty;

    /** Default depth when using `tableOfContent: true` */
    private int $defaultDepth;

    public function __construct(
        string $tableOfContentProperty = 'tableOfContent',
        string $contentProperty = 'content',
        int $defaultDepth = TableOfContentGenerator::MAX_DEPTH
    ) {
        $this->tableOfContentProperty = $tableOfContentProperty;
        $this->contentProperty = $contentProperty;
        $this->defaultDepth = $defaultDepth;
    }

    public function __invoke(array &$data, string $type, Content $content): void
    {
        $depth = $this->getDepth($data);

        if ($depth === 0) {
            return;
        }

        $crawler = new Crawler($data[$this->contentProperty]);

        try {
            $crawler->html();
        } catch (\Exception $e) {
            // Content is not valid HTML.
            return;
        }

        $data[$this->tableOfContentProperty] = TableOfContentGenerator::getTableOfContent($crawler, $depth);
    }

    private function getDepth(array $data): int
    {
        if (!\array_key_exists($this->tableOfContentProperty, $data)) {
            return 0;
        }

        if (\is_bool($data[$this->tableOfContentProperty]) && $data[$this->tableOfContentProperty]) {
            return $this->defaultDepth;
        }

        return \intval($data[$this->tableOfContentProperty]);
    }
}
