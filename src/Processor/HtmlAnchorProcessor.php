<?php

/*
 * This file is part of the "Tom32i/Content" bundle.
 *
 * @author Thomas Jarrand <thomas.jarrand@gmail.com>
 */

namespace Content\Processor;

use Content\Behaviour\ProcessorInterface;
use Content\Content;
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
        if (!isset($data[$this->property])) {
            return;
        }

        $crawler = new Crawler($data[$this->property]);

        try {
            $crawler->html();
        } catch (\Exception $e) {
            // Content is not valid HTML.
            return;
        }

        $crawler = new Crawler($data[$this->property]);

        foreach ($crawler->filter('h1, h2, h3, h4, h5') as $element) {
            $this->addAnchor($element);
        }

        $data[$this->property] = $crawler->html();
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
