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
 * Set a "LastModified" property based on file date
 */
class LastModifiedPropertyHandler implements PropertyHandlerInterface
{
    /**
     * {@inheritdoc}
     */
    public function isSupported($value): bool
    {
        return !$value;
    }

    /**
     * {@inheritdoc}
     */
    public function handle($value, array $context)
    {
        /** @var Content $content */
        $content = $context['content'];

        return $content->getLastModified() ? $content->getLastModified()->format(\DateTimeInterface::RFC3339) : null;
    }
}
