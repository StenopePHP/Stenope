<?php

/*
 * This file is part of the "StenopePHP/Stenope" bundle.
 *
 * @author Thomas Jarrand <thomas.jarrand@gmail.com>
 */

namespace Stenope\Bundle\TableOfContent;

use Symfony\Component\DomCrawler\Crawler;

class TableOfContentGenerator
{
    public const MIN_DEPTH = 1; // Don't account for h1 by default
    public const MAX_DEPTH = 6;

    /**
     * @return Headline[]
     */
    public static function getTableOfContent(Crawler $crawler, int $depth): array
    {
        $tableOfContent = [];
        $previous = null;

        /* @var \DOMElement $element */
        foreach ($crawler->filter(static::getFilters($depth)) as $element) {
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

    private static function getFilters(int $depth): string
    {
        $boundedDepth = max(static::MIN_DEPTH, min($depth, static::MAX_DEPTH));

        return implode(
            ', ',
            array_map(
                fn ($index) => 'h' . ($index + 1),
                array_keys(array_fill(static::MIN_DEPTH, $boundedDepth - static::MIN_DEPTH, null))
            )
        );
    }
}
