<?php

/*
 * This file is part of the "StenopePHP/Stenope" bundle.
 *
 * @author Thomas Jarrand <thomas.jarrand@gmail.com>
 */

namespace Stenope\Bundle\Service;

use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;

class ContentUtils
{
    private static ?PropertyAccessorInterface $propertyAccessor = null;

    /**
     * Get max value of the given property in the given content list
     */
    public static function max(array $contents, string $property)
    {
        return max(static::getAttributes($contents, $property));
    }

    /**
     * Get min value of the given property in the given content list
     */
    public static function min(array $contents, string $property)
    {
        return min(static::getAttributes($contents, $property));
    }

    /**
     * List all values for given property in the given list of contents
     */
    public static function getAttributes(array $contents, string $property): array
    {
        return array_map(
            fn ($content) => static::getPropertyAccessor()->getValue($content, $property),
            $contents
        );
    }

    private static function getPropertyAccessor(): PropertyAccessorInterface
    {
        if (!static::$propertyAccessor) {
            static::$propertyAccessor = PropertyAccess::createPropertyAccessor();
        }

        return static::$propertyAccessor;
    }
}
