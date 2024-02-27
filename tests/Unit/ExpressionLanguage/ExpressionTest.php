<?php

/*
 * This file is part of the "StenopePHP/Stenope" bundle.
 *
 * @author Thomas Jarrand <thomas.jarrand@gmail.com>
 * @author Maxime Steinhausser <maxime.steinhausser@gmail.com>
 */

namespace Stenope\Bundle\Tests\Unit\ExpressionLanguage;

use PHPUnit\Framework\TestCase;
use function Stenope\Bundle\ExpressionLanguage\expr;
use Stenope\Bundle\ExpressionLanguage\Expression;
use function Stenope\Bundle\ExpressionLanguage\exprOr;
use Symfony\Bridge\PhpUnit\ClassExistsMock;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage;

class ExpressionTest extends TestCase
{
    /**
     * @runInSeparateProcess
     */
    public function testUnavailableExpressionLanguageHint(): void
    {
        ClassExistsMock::register(Expression::class);
        ClassExistsMock::withMockedClasses([ExpressionLanguage::class => false]);

        try {
            $this->expectException(\LogicException::class);
            $this->expectExceptionMessage('You must install the Symfony ExpressionLanguage component ("symfony/expression-language")');

            new Expression('_.foo');
        } finally {
            ClassExistsMock::withMockedClasses([ExpressionLanguage::class => true]);
        }
    }

    public function testCombineAnd(): void
    {
        self::assertSame('(_.active) and (!_.outdated)', (string) Expression::combineAnd('_.active', '!_.outdated'));
        self::assertSame('(_.active) and (!_.outdated)', (string) expr('_.active', '!_.outdated'));
    }

    public function testCombineOr(): void
    {
        self::assertSame('(!_.active) or (_.outdated)', (string) Expression::combineOr('!_.active', '_.outdated'));
        self::assertSame('(!_.active) or (_.outdated)', (string) exprOr('!_.active', '_.outdated'));
    }
}
