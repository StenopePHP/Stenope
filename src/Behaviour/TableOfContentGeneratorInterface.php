<?php

/*
 * This file is part of the "StenopePHP/Stenope" bundle.
 *
 * @author Thomas Jarrand <thomas.jarrand@gmail.com>
 */

namespace Stenope\Bundle\Behaviour;

interface TableOfContentGeneratorInterface
{
    /**
     * @param string $content An HTML DOM
     * @param int|null $fromDepth Minimum title to include (default H1)
     * @param int|null $toDepth Maximum title to include (default H6)
     *
     * @return Headline[]
     */
    public function getTableOfContent(string $content, ?int $fromDepth = null, ?int $toDepth = null): array;
}
