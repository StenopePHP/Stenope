<?php

/*
 * This file is part of the "StenopePHP/Stenope" bundle.
 *
 * @author Thomas Jarrand <thomas.jarrand@gmail.com>
 */

namespace Stenope\Bundle\TableOfContent;

use Stenope\Bundle\Behaviour\TableOfContentGeneratorInterface;
use Symfony\Component\DomCrawler\Crawler;

class CrawlerTableOfContentGenerator implements TableOfContentGeneratorInterface
{
    public const MIN_DEPTH = 1;
    public const MAX_DEPTH = 6;

    /**
     * @return Headline[]
     */
    public function getTableOfContent(string $content, ?int $fromDepth = null, ?int $toDepth = null): array
    {

        $crawler = new Crawler($content);

        try {
            $crawler->html();
        } catch (\Exception $e) {
            // Content is not valid HTML.
            return [];
        }

        $filters = $this->getFilters($fromDepth ?? static::MIN_DEPTH, $toDepth ?? static::MAX_DEPTH);
        $tableOfContent = [];
        $previous = null;

        /* @var \DOMElement $element */
        foreach ($crawler->filter($filters) as $element) {
            \assert($element instanceof \DOMElement);
            $level = (int) $element->tagName[1];
            $current = new Headline($level, $element->getAttribute('id'), $element->textContent);
            $parent = $previous !== null ? $previous->getParentForLevel($level) : null;
            $previous = $current;

            if ($parent === null) {
                $tableOfContent[] = $current;
                continue;
            }

            $parent->addChild($current);
        }

        return $tableOfContent;
    }

    private function getFilters(int $fromDepth, int $toDepth): string
    {
        $from = max(static::MIN_DEPTH, min($fromDepth, static::MAX_DEPTH));
        $to = max(static::MIN_DEPTH, min($toDepth, static::MAX_DEPTH));

        return implode(
            ', ',
            array_map(
                fn ($index) => 'h' . $index,
                array_keys(array_fill($from, $to - $from + 1, null))
            )
        );
    }
}
