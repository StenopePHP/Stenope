<?php

/*
 * This file is part of the "Tom32i/Content" bundle.
 *
 * @author Thomas Jarrand <thomas.jarrand@gmail.com>
 */

namespace Content\PropertyHandler;

use Content\Behaviour\PropertyHandlerInterface;

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
        return (new \DateTime('@' . $context['file']->getMTime()))->format(\DateTimeInterface::RFC3339);
    }
}
