<?php

/*
 * This file is part of the "StenopePHP/Stenope" bundle.
 *
 * @author Thomas Jarrand <thomas.jarrand@gmail.com>
 */

namespace Stenope\Decoder;

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
     * {@inheritdoc}
     */
    public function decode($data, $format, array $context = [])
    {
        $crawler = new Crawler($data);

        $attributes = [];

        $crawler->filterXPath('//head/meta')->each(function ($node) use (&$attributes): void {
            $attributes[$node->attr('name')] = $node->attr('content');
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
}
