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
}
