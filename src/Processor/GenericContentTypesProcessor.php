<?php

/*
 * This file is part of the "StenopePHP/Stenope" bundle.
 *
 * @author Thomas Jarrand <thomas.jarrand@gmail.com>
 */

namespace Stenope\Bundle\Processor;

use Stenope\Bundle\Behaviour\ProcessorInterface;
use Stenope\Bundle\Content;
use Stenope\Bundle\Content\GenericContent;

class GenericContentTypesProcessor implements ProcessorInterface
{
    public function __invoke(array &$data, Content $content): void
    {
        if (!is_a($content->getType(), GenericContent::class, true)) {
            return;
        }

        if (isset($data['type']) || !str_contains($content->getSlug(), '/')) {
            return;
        }

        $data['types'] = GenericContent::expandTypes($data['type'] = \dirname($content->getSlug()));
    }
}
