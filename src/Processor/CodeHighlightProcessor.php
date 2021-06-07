<?php

/*
 * This file is part of the "StenopePHP/Stenope" bundle.
 *
 * @author Thomas Jarrand <thomas.jarrand@gmail.com>
 */

namespace Stenope\Bundle\Processor;

use Stenope\Bundle\Behaviour\HighlighterInterface;
use Stenope\Bundle\Behaviour\HtmlCrawlerManagerInterface;
use Stenope\Bundle\Behaviour\ProcessorInterface;
use Stenope\Bundle\Content;
use Stenope\Bundle\Service\HtmlUtils;

/**
 * Apply syntax coloration to code blocs
 */
class CodeHighlightProcessor implements ProcessorInterface
{
    private HighlighterInterface $highlighter;
    private HtmlCrawlerManagerInterface $crawlers;
    private string $property;

    public function __construct(
        HighlighterInterface $highlighter,
        HtmlCrawlerManagerInterface $crawlers,
        string $property = 'content'
    ) {
        $this->highlighter = $highlighter;
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

        foreach ($crawler->filter('code') as $element) {
            $this->highlight($element);
        }

        $this->crawlers->save($content, $data, $this->property);
    }

    private function highlight(\DOMElement $element): void
    {
        if (preg_match('#(language|lang)-([^ ]+)#i', $element->getAttribute('class'), $matches)) {
            $language = $matches[2];
            HtmlUtils::setContent($element, $this->highlighter->highlight(trim($element->nodeValue), $language));
            HtmlUtils::addClass($element->parentNode, 'language-' . $language);
        }
    }
}
