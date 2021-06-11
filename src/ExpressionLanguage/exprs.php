<?php

/*
 * This file is part of the "StenopePHP/Stenope" bundle.
 *
 * @author Thomas Jarrand <thomas.jarrand@gmail.com>
 */

namespace Stenope\Bundle\ExpressionLanguage;

function expr(string ...$exprs): Expression
{
    return Expression::combineAnd(...$exprs);
}

function exprOr(string ...$exprs): Expression
{
    return Expression::combineOr(...$exprs);
}
