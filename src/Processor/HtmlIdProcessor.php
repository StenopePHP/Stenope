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
 * Add ids to title in the content
 */
class HtmlIdProcessor implements ProcessorInterface
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
        } catch (\Exception $exception) {
            // Content is not valid HTML.
            return;
        }

        foreach ($crawler->filter('h1, h2, h3, h4, h5, h6') as $element) {
            $this->setIdFromContent($element);
        }

        foreach ($crawler->filter('code, quote') as $element) {
            $this->setIdFromHashedContent($element);
        }

        foreach ($crawler->filter('img') as $element) {
            $this->setIdForImage($element);
        }

        $data[$this->property] = $crawler->html();
    }

    private function setIdFromContent(\DOMElement $element): void
    {
        if (!$id = $element->getAttribute('id')) {
            $element->setAttribute('id', $this->slugify($element->textContent));
        }
    }

    private function setIdFromHashedContent(\DOMElement $element): void
    {
        if (!$id = $element->getAttribute('id')) {
            $element->setAttribute('id', $this->hash($element->textContent));
        }
    }

    private function setIdForImage(\DOMElement $element): void
    {
        if (!$id = $element->getAttribute('id')) {
            $name = $element->getAttribute('alt') ?: \basename($element->getAttribute('src'));
            $element->setAttribute('id', $this->slugify($name));
        }
    }

    /**
     * Get an url valid ID from the given value
     */
    private function slugify(string $value, int $maxLength = 32): string
    {
        return trim(preg_replace('#[^a-z0-9]+#i', '-', strtolower(substr($value, 0, $maxLength))), '-');
    }

    /**
     * Get an url valid ID from hashed value
     */
    private function hash(string $value, string $algo = 'md5'): string
    {
        return hash($algo, $value);
    }
}
