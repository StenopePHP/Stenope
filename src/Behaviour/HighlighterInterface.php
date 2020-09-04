<?php

/*
 * This file is part of the "Tom32i/Content" bundle.
 *
 * @author Thomas Jarrand <thomas.jarrand@gmail.com>
 */

namespace Content\Behaviour;

interface HighlighterInterface
{
    /**
     * Highlight the given code
     */
    public function highlight(string $value, string $language): string;
}
