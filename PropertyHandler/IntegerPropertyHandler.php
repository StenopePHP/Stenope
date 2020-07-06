<?php

/*
 * This file is part of the "Tom32i/Content" bundle.
 *
 * @author Thomas Jarrand <thomas.jarrand@gmail.com>
 */

namespace Content\PropertyHandler;

use Content\Behaviour\PropertyHandlerInterface;

/**
 * Parse the given property as integer
 */
class IntegerPropertyHandler implements PropertyHandlerInterface
{
    public function isSupported($value): bool
    {
        try {
            \intval($value);
        } catch (\Exception $e) {
            return false;
        }

        return true;
    }

    public function handle($value, array $context)
    {
        return \intval($value);
    }
}
