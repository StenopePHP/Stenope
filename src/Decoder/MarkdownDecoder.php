<?php

/*
 * This file is part of the "Tom32i/Content" bundle.
 *
 * @author Thomas Jarrand <thomas.jarrand@gmail.com>
 */

namespace Content\Decoder;

use Content\Service\Parsedown;
use Symfony\Component\Serializer\Encoder\DecoderInterface;
use Symfony\Component\Yaml\Yaml;

/**
 * Parse Markdown data
 */
class MarkdownDecoder implements DecoderInterface
{
    /**
     * Supported format
     */
    public const FORMAT = 'markdown';
    private const HEAD_SEPARATOR = '---';

    /**
     * Markdown parser
     */
    private Parsedown $parser;

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

    private function parseYaml(string $data): array
    {
        return Yaml::parse($data, true);
    }

    /**
     * Parse Mardown to return HTML
     */
    private function markdownify(string $data): string
    {
        return $this->parser->parse($data);
    }
}
