<?php

/*
 * This file is part of the "StenopePHP/Stenope" bundle.
 *
 * @author Thomas Jarrand <thomas.jarrand@gmail.com>
 */

namespace Stenope\Bundle\Provider;

use Stenope\Bundle\Content;
use Stenope\Bundle\ReverseContent\Context;

interface ReversibleContentProviderInterface extends ContentProviderInterface
{
    public function reverse(Context $context): ?Content;
}
