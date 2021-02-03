<?php

/*
 * This file is part of the "StenopePHP/Stenope" bundle.
 *
 * @author Thomas Jarrand <thomas.jarrand@gmail.com>
 */

namespace Stenope\Bundle\Exception;

class ContentNotFoundException extends \InvalidArgumentException implements ExceptionInterface
{
    public function __construct(string $type, string $id)
    {
        parent::__construct(sprintf('Content not found for type "%s" and id "%s".', $type, $id));
    }
}
