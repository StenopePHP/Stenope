<?php

/*
 * This file is part of the "StenopePHP/Stenope" bundle.
 *
 * @author Thomas Jarrand <thomas.jarrand@gmail.com>
 */

namespace Stenope\Bundle\Behaviour;

interface HighlighterInterface
{
    /**
     * Highlight the given code
     */
    public function highlight(string $value, string $language): string;
}
