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
 * Add anchor to elements with ids
 */
class HtmlAnchorProcessor implements ProcessorInterface
{
    private HtmlCrawlerManagerInterface $crawlers;
    private string $property;
    private string $selector;

    public function __construct(
        HtmlCrawlerManagerInterface $crawlers,
        string $property = 'content',
        string $selector = 'h1, h2, h3, h4, h5'
    ) {
        $this->crawlers = $crawlers;
        $this->property = $property;
        $this->selector = $selector;
    }

    public function __invoke(array &$data, Content $content): void
    {
        if (!isset($data[$this->property])) {
            return;
        }

        $crawler = $this->crawlers->get($content, $data, $this->property);

        if (!$crawler) {
            return;
        }

        foreach ($crawler->filter($this->selector) as $element) {
            $this->addAnchor($element);
        }

        $this->crawlers->save($content, $data, $this->property);
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

        $child->appendXML(sprintf('<a href="#%s" class="anchor"></a>', $id));

        $element->appendChild($child);
    }
}
