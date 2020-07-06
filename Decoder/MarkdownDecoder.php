<?php

/*
 * This file is part of the "Tom32i/Content" bundle.
 *
 * @author Thomas Jarrand <thomas.jarrand@gmail.com>
 */

namespace Content\Decoder;

use Content\Behaviour\ContentDecoderInterface;
use Content\Service\Parsedown;
use Symfony\Component\Yaml\Yaml;

/**
 * Parse Markdown data
 */
class MarkdownDecoder implements ContentDecoderInterface
{
    /**
     * Supported format
     */
    const FORMAT = 'markdown';

    /**
     * Head separator
     */
    const HEAD_SEPARATOR = '---';

    /**
     * Markdown parser
     *
     * @var Parsdown
     */
    private $parser;

    /**
     * Constructor
     */
    public function __construct(Parsedown $parser)
    {
        $this->parser = $parser;
    }

    /**
     * {@inheritdoc}
     */
    public function decode($data, $format, array $context = [])
    {
        $separator = static::HEAD_SEPARATOR;
        $start = strpos($data, $separator);
        $stop = strpos($data, $separator, 1);
        $length = \strlen($separator) + 1;

        if ($start === 0 && $stop) {
            return array_merge(
                $this->parseYaml(substr($data, $start + $length, $stop - $length)),
                ['content' => $this->markdownify(substr($data, $stop + $length))]
            );
        }

        return ['content' => $this->markdownify($data)];
    }

    /**
     * {@inheritdoc}
     */
    public function supportsDecoding($format)
    {
        return self::FORMAT === $format;
    }

    /**
     * Parse YAML
     *
     * @param string $data
     *
     * @return array
     */
    private function parseYaml($data)
    {
        return Yaml::parse($data, true);
    }

    /**
     * Parse Mardown to return HTML
     *
     * @param string $data
     *
     * @return string
     */
    private function markdownify($data)
    {
        return $this->parser->parse($data);
    }
}
