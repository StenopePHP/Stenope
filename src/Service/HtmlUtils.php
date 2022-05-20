<?php

/*
 * This file is part of the "StenopePHP/Stenope" bundle.
 *
 * @author Thomas Jarrand <thomas.jarrand@gmail.com>
 */

namespace Stenope\Bundle\Service;

class HtmlUtils
{
    /**
     * Add class to the given element
     */
    public static function addClass(\DOMElement $element, string $class): void
    {
        $element->setAttribute('class', implode(' ', array_filter([
            trim($element->getAttribute('class')),
            $class,
        ])));
    }

    /**
     * Set element HTML content
     */
    public static function setContent(\DOMElement $element, string $content): void
    {
        $element->nodeValue = '';

        $child = $element->ownerDocument->createDocumentFragment();

        $child->appendXML($content);

        $element->appendChild($child);
    }

    /**
     * Wrap content
     */
    public static function wrapContent(\DOMElement $element, string $wrapTag, array $wrapAttributes = []): void
    {
        $wrapper = $element->ownerDocument->createElement($wrapTag);

        foreach ($wrapAttributes as $key => $value) {
            $wrapper->setAttribute($key, $value);
        }

        foreach ($element->childNodes as $child) {
            $wrapper->appendChild($child);
        }

        $element->appendChild($wrapper);
    }
}
