<?php

/*
 * This file is part of the "Tom32i/Content" bundle.
 *
 * @author Thomas Jarrand <thomas.jarrand@gmail.com>
 */

namespace Content\PropertyHandler;

use Content\Behaviour\PropertyHandlerInterface;

/**
 * Parse the given property as Datetime
 */
class DateTimePropertyHandler implements PropertyHandlerInterface
{
    /**
     * Is data supported?
     */
    public function isSupported($value): bool
    {
        try {
            new \DateTime($value);
        } catch (\Exception $e) {
            return false;
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function handle($value, array $context)
    {
        return new \DateTime($value);
    }
}
