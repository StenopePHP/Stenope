<?php

/*
 * This file is part of the "Tom32i/Content" bundle.
 *
 * @author Thomas Jarrand <thomas.jarrand@gmail.com>
 */

namespace Content\Processor;

use Content\Behaviour\HighlighterInterface;
use Content\Behaviour\ProcessorInterface;
use Content\Content;
use Content\Service\HtmlUtils;
use Symfony\Component\DomCrawler\Crawler;

/**
 * Apply syntax coloration to code blocs
 */
class CodeHighlightProcessor implements ProcessorInterface
{
    private HighlighterInterface $highlighter;
    private string $property;

    public function __construct(HighlighterInterface $highlighter, string $property = 'content')
    {
        $this->highlighter = $highlighter;
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

        foreach ($crawler->filter('code') as $element) {
            $this->highlight($element);
        }

        $data[$this->property] = $crawler->html();
    }

    private function highlight(\DOMElement $element): void
    {
        if (preg_match('#(language|lang)-([^ ]+)#i', $element->getAttribute('class'), $matches)) {
            HtmlUtils::setContent($element, $this->highlighter->highlight(trim($element->nodeValue), $matches[2]));
        }
    }
}
