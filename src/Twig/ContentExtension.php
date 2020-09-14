<?php

/*
 * This file is part of the "Tom32i/Content" bundle.
 *
 * @author Thomas Jarrand <thomas.jarrand@gmail.com>
 */

namespace Content\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class ContentExtension extends AbstractExtension
{
    public function getFunctions()
    {
        return [
            new TwigFunction('content_get', [ContentRuntime::class, 'getContent']),
            new TwigFunction('content_list', [ContentRuntime::class, 'listContents']),
        ];
    }
}
