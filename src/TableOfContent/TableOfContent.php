<?php

/*
 * This file is part of the "StenopePHP/Stenope" bundle.
 *
 * @author Thomas Jarrand <thomas.jarrand@gmail.com>
 */

namespace Stenope\Bundle\TableOfContent;

/**
 * @implements \IteratorAggregate<Headline>
 */
class TableOfContent extends \ArrayObject
{
    /**
     * @param Headline[] $headlines
     */
    public function __construct(array $headlines = [])
    {
        parent::__construct($headlines, 0, \ArrayIterator::class);
    }
}
