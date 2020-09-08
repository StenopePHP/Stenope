<?php

/*
 * This file is part of the "Tom32i/Content" bundle.
 *
 * @author Thomas Jarrand <thomas.jarrand@gmail.com>
 */

namespace Content\Tests\Unit;

use Content\Content;
use Content\ContentManager;
use Content\Provider\ContentProviderInterface;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Symfony\Component\Serializer\Encoder\DecoderInterface;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

class ContentManagerTest extends TestCase
{
    public function testGetContents(): void
    {
        $manager = new ContentManager(
            ($decoder = $this->prophesize(DecoderInterface::class))->reveal(),
            ($denormalizer = $this->prophesize(DenormalizerInterface::class))->reveal(),
            [],
            [
                ($provider1 = $this->prophesize(ContentProviderInterface::class))->reveal(),
                ($provider2 = $this->prophesize(ContentProviderInterface::class))->reveal(),
                ($provider3 = $this->prophesize(ContentProviderInterface::class))->reveal(),
            ],
            null
        );

        $provider1->supports('App\Foo')->willReturn(true);
        $provider1->listContents()->willReturn([
            new Content('foo1', 'Foo 1', 'markdown'),
            new Content('foo2', 'Foo 2', 'html'),
        ]);

        $provider2->supports('App\Foo')->willReturn(false);
        $provider2->listContents()->willReturn([
            new Content('bar1', 'Bar 1', 'markdown'),
        ]);

        $provider3->supports('App\Foo')->willReturn(true);
        $provider3->listContents()->willReturn([
            new Content('foo3', 'Foo 3', 'markdown'),
        ]);

        $decoder
            ->decode(Argument::type('string'), Argument::type('string'))
            ->will(fn ($args) => ['content' => $args[0]])
            ->shouldBeCalledTimes(3)
        ;

        $orders = [2, 1, 3];
        $denormalizer
            ->denormalize(Argument::type('array'), 'App\Foo', Argument::any())
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
    }
}
