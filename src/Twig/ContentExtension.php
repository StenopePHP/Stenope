<?php

/*
 * This file is part of the "StenopePHP/Stenope" bundle.
 *
 * @author Thomas Jarrand <thomas.jarrand@gmail.com>
 */

namespace Stenope\Bundle\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * @final
 */
class ContentExtension extends AbstractExtension
{
    public function getFunctions(): array
    {
        return [
            new TwigFunction('content_get', [ContentRuntime::class, 'getContent']),
            new TwigFunction('content_list', [ContentRuntime::class, 'listContents']),
            new TwigFunction('content_expr', '\Stenope\Bundle\ExpressionLanguage\expr'),
            new TwigFunction('content_expr_or', '\Stenope\Bundle\ExpressionLanguage\exprOr'),
        ];
    }
}
