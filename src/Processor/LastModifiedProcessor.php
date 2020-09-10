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
 * Set a "LastModified" property based on file date
 */
class LastModifiedProcessor implements ProcessorInterface
{
    public static function isSupported($value): bool
    {
        return \is_null($value);
    }

    public function __invoke(array &$data, array $context): void
    {
        if (!static::isSupported($data['lastModified'] ?? null)) {
            return;
        }

        /** @var Content $content */
        $content = $context['content'];

        $data['lastModified'] = $content->getLastModified() ? $content->getLastModified()->format(\DateTimeInterface::RFC3339) : null;
    }
}
