<?php

/*
 * This file is part of the "StenopePHP/Stenope" bundle.
 *
 * @author Thomas Jarrand <thomas.jarrand@gmail.com>
 */

namespace Stenope\Bundle\Content;

class GenericContent extends \stdClass
{
    public string $slug;
    public ?string $template = null;
    public ?string $type = null;
    /** @var string[] */
    public array $types = [];

    public static function expandTypes(string $type): array
    {
        $types = [$base = $type];

        while (true) {
            if ('.' === $base = \dirname($base)) {
                break;
            }
            $types[] = $base;
        }

        return $types;
    }
}
