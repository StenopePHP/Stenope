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
 * Add ids to title in the content
 */
class HtmlIdProcessor implements ProcessorInterface
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

        $crawler->filter('h1')->each(fn ($node) => $this->setIdFromContent($node));
        $crawler->filter('h2')->each(fn ($node) => $this->setIdFromContent($node));
        $crawler->filter('h3')->each(fn ($node) => $this->setIdFromContent($node));
        $crawler->filter('h4')->each(fn ($node) => $this->setIdFromContent($node));
        $crawler->filter('h5')->each(fn ($node) => $this->setIdFromContent($node));
        $crawler->filter('code')->each(fn ($node) => $this->setIdFromHashedContent($node));
        $crawler->filter('quote')->each(fn ($node) => $this->setIdFromHashedContent($node));
        $crawler->filter('img')->each(fn ($node) => $this->setIdForImage($node));

        $data['content'] = $crawler->html();
    }

    /**
     * Set id from content
     */
    private function setIdFromContent(Crawler $node): void
    {
        $element = $node->getNode(0);

        if (!$id = $element->getAttribute('id')) {
            $element->setAttribute('id', $this->slugify($node->text()));
        }
    }

    /**
     * Set id from multilign content
     */
    private function setIdFromHashedContent(Crawler $node): void
    {
        $element = $node->getNode(0);

        if (!$id = $element->getAttribute('id')) {
            $element->setAttribute('id', hash('md5', $node->text()));
        }
    }

    /**
     * Set id from attribute
     */
    private function setIdForImage(Crawler $node): void
    {
        $element = $node->getNode(0);

        if (!$id = $element->getAttribute('id')) {
            if ($alt = $element->getAttribute('alt')) {
                $element->setAttribute('id', $this->slugify($alt));
            } else {
                $element->setAttribute('id', $this->hash($element->getAttribute('src')));
            }
        }
    }

    /**
     * Get a url valid ID from the given value
     */
    private function slugify(string $value, int $maxLength = 32): string
    {
        return trim(preg_replace('#[^a-z]+#i', '-', strtolower(substr($value, 0, $maxLength))), '-');
    }

    private function hash(string $value, string $algo = 'md5'): string
    {
        return hash($algo, $value);
    }
}
