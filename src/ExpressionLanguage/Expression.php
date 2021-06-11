<?php

/*
 * This file is part of the "StenopePHP/Stenope" bundle.
 *
 * @author Thomas Jarrand <thomas.jarrand@gmail.com>
 */

namespace Stenope\Bundle\ExpressionLanguage;

use Symfony\Component\ExpressionLanguage\Expression as BaseExpression;

if (!class_exists(\Symfony\Component\ExpressionLanguage\ExpressionLanguage::class)) {
    throw new \LogicException(sprintf('You must install the Symfony ExpressionLanguage component ("symfony/expression-language") in order to use the "%s" class.', Expression::class));
}

final class Expression extends BaseExpression
{
    public static function combineAnd(string ...$exprs): self
    {
        if (\count($exprs) === 1) {
            return new Expression(...$exprs);
        }

        return new self(implode(' and ', array_map(static fn ($e) => sprintf('(%s)', $e), $exprs)));
    }

    public static function combineOr(string ...$exprs): self
    {
        if (\count($exprs) === 1) {
            return new Expression(...$exprs);
        }

        return new self(implode(' or ', array_map(static fn ($e) => sprintf('(%s)', $e), $exprs)));
    }

    public function getExpression(): string
    {
        return $this->expression;
    }
}
