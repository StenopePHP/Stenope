<?php

/*
 * This file is part of the "StenopePHP/Stenope" bundle.
 *
 * @author Thomas Jarrand <thomas.jarrand@gmail.com>
 */

namespace Stenope\Bundle\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Stenope\Bundle\Behaviour\ContentManagerAwareInterface;
use Stenope\Bundle\Behaviour\ProcessorInterface;
use Stenope\Bundle\Content;
use Stenope\Bundle\ContentManager;
use Stenope\Bundle\Provider\ContentProviderInterface;
use Stenope\Bundle\Provider\ReversibleContentProviderInterface;
use Stenope\Bundle\ReverseContent\RelativeLinkContext;
use Symfony\Component\Serializer\Encoder\DecoderInterface;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

class ContentManagerTest extends TestCase
{
    use ProphecyTrait;

    public function testGetContents(): void
    {
        $manager = new ContentManager(
            ($decoder = $this->prophesize(DecoderInterface::class))->reveal(),
            ($denormalizer = $this->prophesize(DenormalizerInterface::class))->reveal(),
            [
                ($provider1 = $this->prophesize(ContentProviderInterface::class))->reveal(),
                ($provider2 = $this->prophesize(ContentProviderInterface::class))->reveal(),
                ($provider3 = $this->prophesize(ContentProviderInterface::class))->reveal(),
            ],
            [
                ($processor = $this->prophesize(ContentManagerAwareProcessorInterface::class))->reveal(),
            ],
            null
        );

        $provider1->supports('App\Foo')->willReturn(true);
        $provider1->listContents()->willReturn([
            new Content('foo1', 'App\Foo', 'Foo 1', 'markdown'),
            new Content('foo2', 'App\Foo', 'Foo 2', 'html'),
        ]);

        $provider2->supports('App\Foo')->willReturn(false);
        $provider2->listContents()->willReturn([
            new Content('bar1', 'App\Foo', 'Bar 1', 'markdown'),
        ]);

        $provider3->supports('App\Foo')->willReturn(true);
        $provider3->listContents()->willReturn([
            new Content('foo3', 'App\Foo', 'Foo 3', 'markdown'),
        ]);

        $decoder
            ->decode(Argument::type('string'), Argument::type('string'))
            ->will(fn ($args) => ['content' => $args[0]])
            ->shouldBeCalledTimes(3)
        ;

        $processor
            ->__invoke(Argument::type('array'), Argument::type('string'), Argument::type(Content::class))
            ->shouldBeCalled()
        ;

        $processor->setContentManager($manager)->shouldBeCalledOnce();

        $orders = [2, 1, 3];
        $denormalizer
            ->denormalize(Argument::type('array'), 'App\Foo', Argument::any(), Argument::any())
            ->will(function ($args) use (&$orders) {
                [$data] = $args;
                $std = new \stdClass();
                $std->content = $data['content'];
                $std->order = current($orders);
                next($orders);

                return $std;
            })
            ->shouldBeCalledTimes(3)
        ;
        self::assertSame([
            'Foo 1',
            'Foo 2',
            'Foo 3',
        ], array_column($manager->getContents('App\Foo'), 'content'), 'no sort');

        self::assertSame([
            'Foo 2',
            'Foo 1',
            'Foo 3',
        ], array_column($manager->getContents('App\Foo', 'order'), 'content'), 'asc order, directly as string');

        self::assertSame([
            'Foo 3',
            'Foo 1',
            'Foo 2',
        ], array_column($manager->getContents('App\Foo', ['order' => false]), 'content'), 'desc order');

        self::assertSame([
            'Foo 2',
            'Foo 1',
            'Foo 3',
        ], array_column($manager->getContents('App\Foo', fn ($a, $b) => $a->order <=> $b->order), 'content'), 'ordered by function');

        self::assertSame([
            'Foo 1',
        ], array_column($manager->getContents('App\Foo', null, ['content' => 'Foo 1']), 'content'), 'filtered by key');

        self::assertSame([
            'Foo 2',
        ], array_column($manager->getContents('App\Foo', null, fn ($foo) => $foo->content === 'Foo 2'), 'content'), 'filtered by function');
    }

    public function testReverseContent(): void
    {
        $manager = new ContentManager(
            ($decoder = $this->prophesize(DecoderInterface::class))->reveal(),
            ($denormalizer = $this->prophesize(DenormalizerInterface::class))->reveal(),
            [
                ($provider = $this->prophesize(ContentProviderInterface::class))->reveal(),
                ($reversibleProvider = $this->prophesize(ReversibleContentProviderInterface::class))->reveal(),
            ],
            [],
        );

        $provider->supports(Argument::any())->shouldNotBeCalled();
        $decoder->decode(Argument::any())->shouldNotBeCalled();
        $denormalizer->denormalize(Argument::any())->shouldNotBeCalled();

        $context = new RelativeLinkContext(
            ['path' => '/workspace/project/bar/baz/baz.md'],
            '../../foo.md',
        );

        $reversibleProvider->reverse($context)->shouldBeCalledOnce()
            ->willReturn($content = new Content('bar1', 'App\Foo', 'Bar 1', 'markdown'))
        ;

        self::assertSame($content, $manager->reverseContent($context), 'content found');

        $context = new RelativeLinkContext(
            ['path' => '/workspace/project/bar/baz/baz.md'],
            '../../will-not-find.md',
        );

        $reversibleProvider->reverse($context)->shouldBeCalledOnce()->willReturn(null);

        self::assertNull($manager->reverseContent($context), 'content not found');
    }
}

interface ContentManagerAwareProcessorInterface extends ProcessorInterface, ContentManagerAwareInterface
{
}
