<?php

/*
 * This file is part of the "StenopePHP/Stenope" bundle.
 *
 * @author Thomas Jarrand <thomas.jarrand@gmail.com>
 */

namespace Stenope\Bundle\Service;

use Parsedown as BaseParsedown;

/**
 * Parsedown as a service
 */
class Parsedown extends BaseParsedown
{
    public function __construct()
    {
        $this->BlockTypes['!'][] = 'Image';
        $this->BlockTypes['!'][] = 'Admonition';
    }

    protected function blockAdmonition($line, $block = null)
    {
        if (preg_match('#^!!! (?<types>[^"]{1,})( ?(\"(?<title>.*)\"))?#', $line['text'], $matches)) {
            $types = array_filter(explode(' ', $matches['types']));
            $type = $types[0];
            $classes = implode(' ', array_map('trim', array_map('strtolower', $types)));
            $title = $matches['title'] ?? $type;
            $admonitionContentRef = null;

            $block = [
                '$admonitionContentRef' => &$admonitionContentRef,
                'element' => [
                    'name' => 'div',
                    'handler' => 'elements',
                    'attributes' => [
                        'class' => "admonition $classes",
                    ],
                    'text' => [
                        'title' => [
                            'handler' => 'line',
                            'name' => 'p',
                            'attributes' => [
                                'class' => 'admonition-title',
                            ],
                            'text' => $title,
                        ],
                        'content' => [
                            'handler' => 'line',
                            'name' => 'p',
                            'text' => &$admonitionContentRef,
                        ],
                    ],
                ],
            ];

            // Remove title if explicitly unset:
            if ($title === '') {
                unset($block['element']['text']['title']);
            }

            return $block;
        }

        return null;
    }

    protected function blockAdmonitionContinue($line, $block = null)
    {
        // A blank newline has occurred, or text without indent:
        if (isset($block['interrupted']) || $line['indent'] < 4) {
            return null;
        }

        $previous = $block['$admonitionContentRef'] ?? "\n";
        $indent = $line['indent'];
        $current = str_repeat(' ', $indent) . $line['text'];
        // Add the next admonition content line:
        $block['$admonitionContentRef'] = "{$previous}{$current}\n";

        return $block;
    }

    protected function blockAdmonitionComplete($block)
    {
        unset($block['$admonitionContentRef']);

        return $block;
    }

    protected function blockFencedCodeComplete($Block)
    {
        $data = parent::blockCodeComplete($Block);

        $data['element']['attributes']['class'] = 'code-multiline';

        return $data;
    }

    protected function inlineCode($Excerpt)
    {
        $data = parent::inlineCode($Excerpt);

        $data['element']['attributes']['class'] = 'code-inline';

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
}
