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
 * Add target="_blank" to external links
 */
class HtmlExternalLinksProcessor implements ProcessorInterface
{
    private HtmlCrawlerManagerInterface $crawlers;
    private string $property;

    public function __construct(HtmlCrawlerManagerInterface $crawlers, string $property = 'content')
    {
        $this->crawlers = $crawlers;
        $this->property = $property;
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

        foreach ($crawler->filter('a') as $element) {
            $this->processLink($element);
        }

        $this->crawlers->save($content, $data, $this->property);
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
