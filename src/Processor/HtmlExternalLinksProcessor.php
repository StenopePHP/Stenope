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
 * Add target="_blank" to external links
 */
class HtmlExternalLinksProcessor implements ProcessorInterface
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

        foreach ($crawler->filter('a') as $element) {
            $this->processLink($element);
        }

        $data[$this->property] = $crawler->html();
    }

    private function processLink(\DOMElement $element): void
    {
        if ($element->hasAttribute('target')) {
            return;
        }

        if (preg_match('#^(https?:)?//#i', $element->getAttribute('href'))) {
            $element->setAttribute('target', '_blank');
        }
    }
}
