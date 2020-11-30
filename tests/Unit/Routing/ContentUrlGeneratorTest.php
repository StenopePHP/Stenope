<?php

/*
 * This file is part of the "StenopePHP/Stenope" bundle.
 *
 * @author Thomas Jarrand <thomas.jarrand@gmail.com>
 */

namespace Stenope\Bundle\Tests\Unit\Routing;

use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Stenope\Bundle\Content;
use Stenope\Bundle\Routing\ContentUrlGenerator;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\Routing\RouterInterface;

class ContentUrlGeneratorTest extends TestCase
{
    use ProphecyTrait;

    /** @var \Prophecy\Prophecy\ObjectProphecy|RouterInterface */
    private $router;
    private ContentUrlGenerator $generator;

    protected function setUp(): void
    {
        $this->router = $this->prophesize(RouterInterface::class);

        $this->generator = new ContentUrlGenerator($this->router->reveal());
    }

    public function testGenerate(): void
    {
        $collection = new RouteCollection();
        $collection->add('foo', new Route('/foo', [], [], [
            'stenope' => [
                'show' => [
                    'class' => 'Foo',
                    'slug' => 'slug',
                ],
            ],
        ]));

        $this->router->getRouteCollection()->shouldBeCalledOnce()->willReturn($collection);
        $this->router->generate('foo', [
            'slug' => 'bar',
        ])->shouldBeCalledOnce()->willReturn('/foo/bar');

        self::assertSame('/foo/bar', $this->generator->generate(new Content('bar', 'Foo', 'rawContent', 'markdown')));
    }

    public function testGenerateThrowsOnMissingMainRoute(): void
    {
        $this->router->getRouteCollection()->shouldBeCalledOnce()->willReturn(new RouteCollection());
        $this->router->generate(Argument::any())->shouldNotBeCalled();

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('No main route was defined for type "Foo"');

        $this->generator->generate(new Content('bar', 'Foo', 'rawContent', 'markdown'));
    }
}
