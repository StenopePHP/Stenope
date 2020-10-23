<?php

/*
 * This file is part of the "StenopePHP/Stenope" bundle.
 *
 * @author Thomas Jarrand <thomas.jarrand@gmail.com>
 */

namespace Stenope\Bundle\Twig;

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
