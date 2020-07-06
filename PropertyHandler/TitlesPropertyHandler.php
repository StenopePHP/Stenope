<?php

/*
 * This file is part of the "Tom32i/Content" bundle.
 *
 * @author Thomas Jarrand <thomas.jarrand@gmail.com>
 */

namespace Content\PropertyHandler;

use Content\Behaviour\PropertyHandlerInterface;
use Symfony\Component\DomCrawler\Crawler;

/**
 * Add ids to title in the content
 */
class TitlesPropertyHandler implements PropertyHandlerInterface
{
    public function isSupported($value): bool
    {
        if (!$value) {
            return false;
        }

        try {
            (new Crawler($value))->html();
        } catch (\Exception $e) {
            return false;
        }

        return true;
    }

    public function handle($value, array $context)
    {
        $crawler = new Crawler($value);

        $crawler->filter('h1')->each(function (Crawler $node): void { $this->setTitleId($node); });
        $crawler->filter('h2')->each(function (Crawler $node): void { $this->setTitleId($node); });
        $crawler->filter('h3')->each(function (Crawler $node): void { $this->setTitleId($node); });
        $crawler->filter('h4')->each(function (Crawler $node): void { $this->setTitleId($node); });
        $crawler->filter('h5')->each(function (Crawler $node): void { $this->setTitleId($node); });
        //$crawler->filter('code')->each(function (Crawler $node) { $this->setTitleId($node); });
        //$crawler->filter('img')->each(function (Crawler $node) { $this->setTitleId($node); });

        return $crawler->html();
    }

    /**
     * Set title id and add anchor
     */
    private function setTitleId(Crawler $node): void
    {
        $element = $node->getNode(0);

        if (!$id = $element->getAttribute('id')) {
            $id = $this->getId($node->text());
            $element->setAttribute('id', $id);
        }

        $child = $element->ownerDocument->createDocumentFragment();

        $child->appendXML('<a href="#' . $id . '" class="anchor"></a>');

        $element->appendChild($child);
    }

    /**
     * Get ID for the given block
     */
    protected function getId(string $content): string
    {
        return trim(preg_replace('#[^a-z]+#i', '-', strtolower($content)), '-');
    }
}
