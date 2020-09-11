<?php

namespace Content\Service;

use Symfony\Component\DomCrawler\Crawler;

class HtmlUtils
{
    /**
     * Add class to the given element
     */
    static public function addClass(\DomElement $element, string $class): void
    {
        $element->setAttribute('class', implode(' ', array_filter([
            trim($element->getAttribute('class')),
            $class,
        ])));
    }

    /**
     * Set element HTML content
     */
    static public function setContent(\DomElement $element, string $content): void
    {
        $element->nodeValue = '';

        $child = $element->ownerDocument->createDocumentFragment();

        $child->appendXML($content);

        $element->appendChild($child);
    }
}
