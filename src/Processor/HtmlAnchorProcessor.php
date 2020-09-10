<?php

/*
 * This file is part of the "Tom32i/Content" bundle.
 *
 * @author Thomas Jarrand <thomas.jarrand@gmail.com>
 */

namespace Content\Processor;

use Content\Behaviour\ProcessorInterface;
use Symfony\Component\DomCrawler\Crawler;

/**
 * Add anchor to elements with ids
 */
class HtmlAnchorProcessor implements ProcessorInterface
{
    public function __invoke(array &$data, array $context): void
    {
        $crawler = new Crawler($data['content'] ?? null);

        try {
            $crawler->html();
        } catch (\Exception $e) {
            // Content is not valid HTML.
            return;
        }

        $crawler = new Crawler($data['content']);

        $crawler->filter('h1')->each(fn ($node) => $this->addAnchor($node));
        $crawler->filter('h2')->each(fn ($node) => $this->addAnchor($node));
        $crawler->filter('h3')->each(fn ($node) => $this->addAnchor($node));
        $crawler->filter('h4')->each(fn ($node) => $this->addAnchor($node));
        $crawler->filter('h5')->each(fn ($node) => $this->addAnchor($node));

        $data['content'] = $crawler->html();
    }

    /**
     * Set title id and add anchor
     */
    private function addAnchor(Crawler $node): void
    {
        $element = $node->getNode(0);
        $child = $element->ownerDocument->createDocumentFragment();

        if (!$id = $element->getAttribute('id')) {
            return;
        }

        $child->appendXML('<a href="#' . $element->getAttribute('id') . '" class="anchor"></a>');

        $element->appendChild($child);
    }
}
