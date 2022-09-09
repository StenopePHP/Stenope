<?php

/*
 * This file is part of the "StenopePHP/Stenope" bundle.
 *
 * @author Thomas Jarrand <thomas.jarrand@gmail.com>
 */

namespace Stenope\Bundle\Tests\Unit\HttpKernel\Controller\ArgumentResolver;

use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Stenope\Bundle\Content;
use Stenope\Bundle\ContentManagerInterface;
use Stenope\Bundle\HttpKernel\Controller\ArgumentResolver\ContentArgumentResolver;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Controller\ValueResolverInterface;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;

class ContentArgumentResolverTest extends TestCase
{
    use ProphecyTrait;

    private ContentArgumentResolver $resolver;

    /** @var ContentManagerInterface|ObjectProphecy */
    private ObjectProphecy $manager;

    protected function setUp(): void
    {
        $this->resolver = new ContentArgumentResolver(
            ($this->manager = $this->prophesize(ContentManagerInterface::class))->reveal()
        );
    }

    public function provideSupportsData(): iterable
    {
        yield 'non-nullable, supported content class' => [
            'request' => new Request([], [], ['foo' => 'foo-1']),
            'argument' => new ArgumentMetadata('foo', 'App\Foo', false, false, null, false),
            'expected' => true,
        ];

        yield 'non-nullable, non-supported content class' => [
            'request' => new Request([], [], ['bar' => 'bar-1']),
            'argument' => new ArgumentMetadata('bar', 'App\Bar', false, false, null, false),
            'expected' => false,
        ];

        yield 'nullable, supported content class, but no matching request attribute (or null)' => [
            'request' => new Request(),
            'argument' => new ArgumentMetadata('foo', 'App\Foo', false, false, null, true),
            'expected' => false,
        ];

        yield 'non-nullable, supported content class, and no matching request attribute (or null)' => [
            'request' => new Request([], [], ['foo' => 'foo-1']),
            'argument' => new ArgumentMetadata('foo', 'App\Foo', false, false, null, false),
            'expected' => true,
        ];
    }

    /**
     * @dataProvider provideSupportsData
     */
    public function testResolvesSomething(Request $request, ArgumentMetadata $argument, bool $expected): void
    {
        if (!interface_exists(ValueResolverInterface::class)) {
            $this->markTestSkipped('Symfony <6.2');
        }

        $this->manager->supports('App\Foo')->willReturn(true);
        $this->manager->supports('App\Bar')->willReturn(false);

        $this->manager->getContent(Argument::cetera())->willReturn(new Content('foo-1', 'App\Foo', 'Foo 1', 'markdown'));

        if ($expected) {
            self::assertNotEmpty($this->resolver->resolve($request, $argument));
        } else {
            self::assertEmpty($this->resolver->resolve($request, $argument));
        }
    }

    public function testResolve(): void
    {
        if (!interface_exists(ValueResolverInterface::class)) {
            $this->markTestSkipped('Symfony <6.2');
        }

        $this->manager->getContent('App\Foo', 'foo-1')->willReturn($content = new Content('foo-1', 'App\Foo', 'Foo 1', 'markdown'));

        $this->manager->supports('App\Foo')->willReturn(true);

        $request = new Request([], [], ['foo' => 'foo-1']);
        $argument = new ArgumentMetadata('foo', 'App\Foo', false, false, null, false);

        self::assertSame([$content], $this->resolver->resolve($request, $argument));
    }

    /**
     * @dataProvider provideSupportsData
     */
    public function testSupportsLegacy(Request $request, ArgumentMetadata $argument, bool $expected): void
    {
        if (interface_exists(ValueResolverInterface::class)) {
            $this->markTestSkipped('Deprecated `ArgumentValueResolverInterface`, use `ValueResolverInterface` instead');
        }

        $this->manager->supports('App\Foo')->willReturn(true);
        $this->manager->supports('App\Bar')->willReturn(false);

        self::assertSame($expected, $this->resolver->supports($request, $argument));
    }

    public function testResolveLegacy(): void
    {
        if (interface_exists(ValueResolverInterface::class)) {
            $this->markTestSkipped('Deprecated `ArgumentValueResolverInterface`, use `ValueResolverInterface` instead');
        }

        $this->manager->getContent('App\Foo', 'foo-1')->willReturn($content = new Content('foo-1', 'App\Foo', 'Foo 1', 'markdown'));

        $request = new Request([], [], ['foo' => 'foo-1']);
        $argument = new ArgumentMetadata('foo', 'App\Foo', false, false, null, false);

        self::assertSame([$content], iterator_to_array($this->resolver->resolve($request, $argument)));
    }
}
