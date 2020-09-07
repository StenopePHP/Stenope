<?php

/*
 * This file is part of the "Tom32i/Content" bundle.
 *
 * @author Thomas Jarrand <thomas.jarrand@gmail.com>
 */

namespace Content\Decoder;

use Content\Behaviour\HighlighterInterface;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\Serializer\Encoder\DecoderInterface;

/**
 * Parse Html data
 */
class HtmlDecoder implements DecoderInterface
{
    /**
     * Supported format
     */
    public const FORMAT = 'html';

    /**
     * Code highlighter
     */
    protected HighlighterInterface $highlighter;

    public function __construct(HighlighterInterface $highlighter)
    {
        $this->highlighter = $highlighter;
    }

    /**
     * {@inheritdoc}
     */
    public function decode($data, $format, array $context = [])
    {
        $crawler = new Crawler($data);

        $attributes = [];

        $crawler->filterXPath('//head/meta')->each(function ($node) use (&$attributes): void {
            $attributes[$node->attr('name')] = $node->attr('content');
        });

        $crawler->filter('code')->each(function (Crawler $node): void {
            if ($language = $node->attr('highlight')) {
                $element = $node->getNode(0);
                $this->setContent($element, $this->highlighter->highlight(trim($node->html()), $language));

                $element->removeAttribute('highlight');
                $this->addClass($element, $language);
            }
        });

        return array_merge(
            $attributes,
            [
                'title' => $crawler->filterXPath('//head/title')->text(),
                'content' => $crawler->filterXPath('//body')->html(),
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function supportsDecoding($format)
    {
        return self::FORMAT === $format;
    }

    public function addClass(\DomElement $element, string $class): void
    {
        $element->setAttribute('class', implode(' ', array_filter([
            trim($element->getAttribute('class')),
            $class,
        ])));
    }

    public function setContent(\DomElement $element, string $content): void
    {
        $element->nodeValue = '';

        $child = $element->ownerDocument->createDocumentFragment();

        $child->appendXML($content);

        $element->appendChild($child);
    }
}
