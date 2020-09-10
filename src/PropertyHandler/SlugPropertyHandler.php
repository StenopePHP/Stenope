<?php

/*
 * This file is part of the "Tom32i/Content" bundle.
 *
 * @author Thomas Jarrand <thomas.jarrand@gmail.com>
 */

namespace Content\PropertyHandler;

use Content\Behaviour\PropertyHandlerInterface;
use Content\Content;

/**
 * Set "slug" property from file name if not specified
 */
class SlugPropertyHandler implements PropertyHandlerInterface
{
    public function isSupported($value): bool
    {
        return !$value;
    }

    public function handle($value, array $context)
    {
        /** @var Content $content */
        $content = $context['content'];

        return $content->getSlug();
    }
}
