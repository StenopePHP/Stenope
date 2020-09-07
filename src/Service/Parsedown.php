<?php

/*
 * This file is part of the "Tom32i/Content" bundle.
 *
 * @author Thomas Jarrand <thomas.jarrand@gmail.com>
 */

namespace Content\Service;

use Content\Behaviour\HighlighterInterface;
use Parsedown as BaseParsedown;

/**
 * Improved Parsedown implementation
 */
class Parsedown extends BaseParsedown
{
    /**
     * Code highlighter
     */
    protected ?HighlighterInterface $highlighter;

    public function __construct(?HighlighterInterface $highlighter = null)
    {
        $this->highlighter = $highlighter;
        $this->BlockTypes = array_merge($this->BlockTypes, [
            '!' => ['Image'],
        ]);
    }

    protected function blockCodeComplete($Block)
    {
        // Drop the <pre>
        $Block['element'] = [
            'name' => 'code',
            'text' => $Block['element']['text']['text'],
        ];

        return $Block;
    }

    protected function blockFencedCodeComplete($Block)
    {
        $language = $this->getLanguage($Block);
        $content = $Block['element']['text']['text'];

        // Drop the <pre> + highlight
        $Block['element'] = [
            'name' => 'code',
            'handler' => 'noescape',
            'text' => $this->getCode($content, $language),
            'attributes' => [
                'class' => $language,
            ],
        ];

        return $Block;
    }

    /**
     * {@inheritdoc}
     */
    protected function inlineLink($Excerpt)
    {
        $data = parent::inlineLink($Excerpt);

        if ($data !== null && preg_match('#(https?:)?//#i', $data['element']['attributes']['href'])) {
            $data['element']['attributes']['target'] = '_blank';
        }

        return $data;
    }

    protected function inlineCode($Excerpt)
    {
        $data = parent::inlineCode($Excerpt);

        $data['element']['name'] = 'span';
        $data['element']['attributes']['class'] = 'inline-code';

        return $data;
    }

    protected function blockImage($Line, $Block = null)
    {
        if (preg_match('/^!\[(.*)]\((.+)\)/', $Line['text'], $matches)) {
            $Block = [
                'element' => [
                    'name' => 'img',
                    'attributes' => [
                        'src' => $matches[2],
                        'alt' => $matches[1],
                        'title' => $matches[1],
                    ],
                ],
            ];

            return $Block;
        }
    }

    /**
     * Process code content
     */
    protected function getCode(string $text, ?string $language = null): string
    {
        if ($this->highlighter && $language) {
            return $this->highlighter->highlight($text, $language);
        }

        return self::escape($text);
    }

    /**
     * No espace filter
     */
    protected function noescape(string $text): string
    {
        return $text;
    }

    /**
     * Get language of the given block
     *
     * @param array $Block
     *
     * @return string
     */
    protected function getLanguage($Block): ?string
    {
        if (!isset($Block['element']['text']['attributes'])) {
            return null;
        }

        return substr($Block['element']['text']['attributes']['class'], \strlen('language-'));
    }
}
