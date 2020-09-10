<?php

/*
 * This file is part of the "Tom32i/Content" bundle.
 *
 * @author Thomas Jarrand <thomas.jarrand@gmail.com>
 */

namespace Content\Processor;

use Content\Behaviour\ProcessorInterface;
use Content\Content;

/**
 * Set "slug" property from file name if not specified
 */
class SlugProcessor implements ProcessorInterface
{
    public static function isSupported($value): bool
    {
        return \is_null($value);
    }

    public function __invoke(array &$data, array $context): void
    {
        if (!static::isSupported($data['slug'] ?? null)) {
            return;
        }

        /** @var Content $content */
        $content = $context['content'];

        $data['slug'] = $content->getSlug();
    }
}
