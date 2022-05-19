<?php

/*
 * This file is part of the "StenopePHP/Stenope" bundle.
 *
 * @author Thomas Jarrand <thomas.jarrand@gmail.com>
 */

namespace Stenope\Bundle\Decoder;

use Stenope\Bundle\Service\Parsedown;
use Symfony\Component\Serializer\Encoder\DecoderInterface;
use Symfony\Component\Yaml\Yaml;

/**
 * Parse Markdown data
 *
 * @final
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
    public function decode($data, $format, array $context = []): array
    {
        $content = trim($data);
        $separator = static::HEAD_SEPARATOR;
        $start = strpos($content, $separator);
        $stop = strpos($content, $separator, $start + 1);
        $length = \strlen($separator) + 1;

        if ($start === 0 && $stop) {
            return array_merge(
                $this->parseYaml(substr($content, $start + $length, $stop - $length)),
                ['content' => $this->markdownToHtml(substr($content, $stop + $length))]
            );
        }

        return ['content' => $this->markdownToHtml($content)];
    }

    /**
     * {@inheritdoc}
     */
    public function supportsDecoding($format, array $context = []): bool
    {
        return self::FORMAT === $format;
    }

    private function parseYaml(string $data): array
    {
        return Yaml::parse($data, true);
    }

    private function markdownToHtml(string $data): string
    {
        return $this->parser->parse($data);
    }
}
