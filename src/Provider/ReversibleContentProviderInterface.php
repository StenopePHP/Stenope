<?php

/*
 * This file is part of the "StenopePHP/Stenope" bundle.
 *
 * @author Thomas Jarrand <thomas.jarrand@gmail.com>
 */

namespace Stenope\Bundle\Provider;

use Stenope\Bundle\Content;

interface ReversibleContentProviderInterface extends ContentProviderInterface
{
    public function reverse(array $context): ?Content;
}
