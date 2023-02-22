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
use Symfony\Component\String\Slugger\AsciiSlugger;
use Symfony\Component\String\Slugger\SluggerInterface;

/**
 * Add ids to HTML elements in the content
 */
class HtmlIdProcessor implements ProcessorInterface
{
    private HtmlCrawlerManagerInterface $crawlers;
    private string $property;
    private SluggerInterface $slugger;

    public function __construct(
        HtmlCrawlerManagerInterface $crawlers,
        string $property = 'content',
        ?SluggerInterface $slugger = null
    ) {
        $this->crawlers = $crawlers;
        $this->property = $property;
        $this->slugger = $slugger ?? new AsciiSlugger();
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

        foreach ($crawler->filter('h1, h2, h3, h4, h5, h6') as $element) {
            $this->setIdFromContent($element);
        }

        foreach ($crawler->filter('code, quote') as $element) {
            $this->setIdFromHashedContent($element);
        }

        foreach ($crawler->filter('img') as $element) {
            $this->setIdForImage($element);
        }

        $this->crawlers->save($content, $data, $this->property);
    }

    private function setIdFromContent(\DOMElement $element): void
    {
        if (!$element->getAttribute('id')) {
            $element->setAttribute('id', $this->slugify($element->textContent));
        }
    }

    private function setIdFromHashedContent(\DOMElement $element): void
    {
        if (!$element->getAttribute('id')) {
            $element->setAttribute('id', $this->hash($element->textContent));
        }
    }

    private function setIdForImage(\DOMElement $element): void
    {
        if (!$element->getAttribute('id')) {
            $name = $element->getAttribute('alt') ?: basename($element->getAttribute('src'));
            $element->setAttribute('id', $this->slugify($name));
        }
    }

    /**
     * Get an url valid ID from the given value
     */
    private function slugify(string $value, int $maxLength = 32): string
    {
        return $this->slugger->slug($value)->truncate($maxLength, '', false)->lower();
    }

    /**
     * Get an url valid ID from hashed value
     */
    private function hash(string $value, string $algo = 'md5'): string
    {
        return hash($algo, $value);
    }
}
