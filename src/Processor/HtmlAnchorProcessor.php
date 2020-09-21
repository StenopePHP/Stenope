<?php

/*
 * This file is part of the "StenopePHP/Stenope" bundle.
 *
 * @author Thomas Jarrand <thomas.jarrand@gmail.com>
 */

namespace Stenope\Bundle\Processor;

use Stenope\Bundle\Behaviour\ProcessorInterface;
use Stenope\Bundle\Content;
use Symfony\Component\DomCrawler\Crawler;

/**
 * Add anchor to elements with ids
 */
class HtmlAnchorProcessor implements ProcessorInterface
{
    private string $property;

    public function __construct(string $property = 'content')
    {
        $this->property = $property;
    }

    public function __invoke(array &$data, string $type, Content $content): void
    {
        if (!isset($data[$this->property]) || !$data[$this->property] instanceof Crawler) {
            return;
        }

        $crawler = $data[$this->property];

        foreach ($crawler->filter('h1, h2, h3, h4, h5') as $element) {
            $this->addAnchor($element);
        }
    }

    /**
     * Set title id and add anchor
     */
    private function addAnchor(\DOMElement $element): void
    {
        $child = $element->ownerDocument->createDocumentFragment();

        if (!$id = $element->getAttribute('id')) {
            return;
        }

        $child->appendXML('<a href="#' . $element->getAttribute('id') . '" class="anchor"></a>');

        $element->appendChild($child);
    }
}
