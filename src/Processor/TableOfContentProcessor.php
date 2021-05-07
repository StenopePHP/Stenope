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
use Stenope\Bundle\Behaviour\TableOfContentGeneratorInterface;
use Symfony\Component\DomCrawler\Crawler;

/**
 * Build a table of content from the content titles
 */
class TableOfContentProcessor implements ProcessorInterface
{
    private TableOfContentGeneratorInterface $generator;
    private string $tableOfContentProperty;
    private string $contentProperty;
    private int $minDepth;
    private int $maxDepth;

    public function __construct(
        TableOfContentGeneratorInterface $generator,
        string $tableOfContentProperty = 'tableOfContent',
        string $contentProperty = 'content',
        int $minDepth = 1,
        int $maxDepth = 6
    ) {
        $this->generator = $generator;
        $this->tableOfContentProperty = $tableOfContentProperty;
        $this->contentProperty = $contentProperty;
        $this->minDepth = $minDepth;
        $this->maxDepth = $maxDepth;
    }

    public function __invoke(array &$data, string $type, Content $content): void
    {
        $data[$this->tableOfContentProperty] = $this->generator->getTableOfContent($data[$this->contentProperty], $this->minDepth, $this->maxDepth);
    }
}
