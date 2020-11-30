<?php

/*
 * This file is part of the "StenopePHP/Stenope" bundle.
 *
 * @author Thomas Jarrand <thomas.jarrand@gmail.com>
 */

namespace Stenope\Bundle\Tests\Unit\Routing;

use PHPUnit\Framework\TestCase;
use Stenope\Bundle\Routing\RouteInfo;
use Symfony\Component\Routing\Route;

class RouteInfoTest extends TestCase
{
    public function testIsMainContentRoute(): void
    {
        $info = new RouteInfo('foo', new Route('/foo', [], [], [
            'stenope' => [
                'show' => [
                    'class' => 'Foo',
                    'slug' => 'slug',
                ],
            ],
        ]));

        self::assertTrue($info->isMainContentRoute(), 'is a main route for one of the content types');
        self::assertTrue($info->isMainContentRoute('Foo'), 'is main Foo route');
        self::assertFalse($info->isMainContentRoute('Bar'), 'is not main Bar route');
    }

    public function testIsMainContentRouteThrowsOnNonGETableRoute(): void
    {
        $info = new RouteInfo('foo', new Route('/foo', [], [], [
            'stenope' => [
                'show' => [
                    'class' => 'Foo',
                    'slug' => 'slug',
                ],
            ],
        ], '', [], ['POST']));

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Route "foo" is defined as the main route to render contents of type "Foo", but the GET method is not allowed.');

        $info->isMainContentRoute();
    }

    public function testGetMainContentRouteSlugVariable(): void
    {
        $info = new RouteInfo('foo', new Route('/foo', [], [], [
            'stenope' => [
                'show' => [
                    'class' => 'Foo',
                    'slug' => 'slug',
                ],
            ],
        ]));

        self::assertSame('slug', $info->getMainContentRouteSlugVariable());
    }

    public function testGetMainContentRouteSlugVariableThrowsOnMissingSlugVariable(): void
    {
        $info = new RouteInfo('foo', new Route('/foo', [], [], [
            'stenope' => [
                'show' => [
                    'class' => 'Foo',
                ],
            ],
        ]));

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Route "foo" is defined as a main route to render contents but does not provide the variable used in path to inject the slug.');

        $info->getMainContentRouteSlugVariable();
    }

    public function testGetMainContentRouteSlugVariableThrowOnNonMainContentRoute(): void
    {
        $info = new RouteInfo('foo', new Route('/foo'));

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Route "foo" is not defined as a main route to render contents.');

        $info->getMainContentRouteSlugVariable();
    }
}
