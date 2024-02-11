<?php

/*
 * This file is part of the "StenopePHP/Stenope" bundle.
 *
 * @author Thomas Jarrand <thomas.jarrand@gmail.com>
 * @author Maxime Steinhausser <maxime.steinhausser@gmail.com>
 */

namespace Stenope\Bundle\Tests\Unit\Routing;

use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Stenope\Bundle\Content;
use Stenope\Bundle\Routing\ContentUrlResolver;
use Stenope\Bundle\Routing\ResolveContentRoute;
use Symfony\Component\Routing\RouterInterface;

class ContentUrlResolverTest extends TestCase
{
    use ProphecyTrait;

    /** @var \Prophecy\Prophecy\ObjectProphecy|RouterInterface */
    private $router;

    protected function setUp(): void
    {
        $this->router = $this->prophesize(RouterInterface::class);
    }

    public function testResolveUrl(): void
    {
        $generator = new ContentUrlResolver($this->router->reveal(), [
            'Foo' => $route = new ResolveContentRoute('show_foo', 'foo'),
        ]);

        $this->router->generate($route->getRoute(), [
            $route->getSlug() => 'the-slug',
        ])->shouldBeCalledOnce()->willReturn($url = '/foo/bar');

        self::assertSame($url, $generator->resolveUrl(new Content('the-slug', 'Foo', 'rawContent', 'markdown')));
    }

    public function testGenerateThrowsOnMissingResolveContentRoute(): void
    {
        $generator = new ContentUrlResolver($this->router->reveal(), []);

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('No route was defined to resolve type "Foo". Did you configure "stenope.resolve_links" for this type?');

        $generator->resolveUrl(new Content('the-slug', 'Foo', 'rawContent', 'markdown'));
    }
}
