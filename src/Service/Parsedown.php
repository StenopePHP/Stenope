<?php

/*
 * This file is part of the "Tom32i/Content" bundle.
 *
 * @author Thomas Jarrand <thomas.jarrand@gmail.com>
 */

namespace Content\Service;

use Parsedown as BaseParsedown;

/**
 * Parsedown as a service
 */
class Parsedown extends BaseParsedown
{
    public function __construct()
    {
        $this->BlockTypes = array_merge($this->BlockTypes, [
            '!' => ['Image'],
        ]);
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
