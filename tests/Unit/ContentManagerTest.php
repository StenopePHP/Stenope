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
use Stenope\Bundle\Behaviour\HtmlCrawlerManagerInterface;
use Stenope\Bundle\Behaviour\ProcessorInterface;
use Stenope\Bundle\Content;
use Stenope\Bundle\ContentManager;
use function Stenope\Bundle\ExpressionLanguage\expr;
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
            ($crawlers = $this->prophesize(HtmlCrawlerManagerInterface::class))->reveal(),
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
            ->__invoke(Argument::type('array'), Argument::type(Content::class))
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

        $getResults = static fn (array $results): array => array_combine(array_keys($results), array_column($results, 'content'));

        self::assertSame([
            'foo1' => 'Foo 1',
            'foo2' => 'Foo 2',
            'foo3' => 'Foo 3',
        ], $getResults($manager->getContents('App\Foo')), 'no sort');

        self::assertSame([
            'foo2' => 'Foo 2',
            'foo1' => 'Foo 1',
            'foo3' => 'Foo 3',
        ], $getResults($manager->getContents('App\Foo', 'order')), 'asc order, directly as string');

        self::assertSame([
            'foo3' => 'Foo 3',
            'foo1' => 'Foo 1',
            'foo2' => 'Foo 2',
        ], $getResults($manager->getContents('App\Foo', ['order' => false])), 'desc order');

        self::assertSame([
            'foo2' => 'Foo 2',
            'foo1' => 'Foo 1',
            'foo3' => 'Foo 3',
        ], $getResults($manager->getContents('App\Foo', fn ($a, $b) => $a->order <=> $b->order)), 'ordered by function');

        self::assertSame([
            'foo1' => 'Foo 1',
        ], $getResults($manager->getContents('App\Foo', null, ['content' => 'Foo 1'])), 'filtered by key');

        self::assertSame([
            'foo1' => 'Foo 1',
        ], $getResults($manager->getContents(
            'App\Foo',
            null,
            ['content' => static fn ($content) => $content === 'Foo 1'],
        )), 'filtered with a property function');

        self::assertSame([
            'foo2' => 'Foo 2',
        ], $getResults($manager->getContents('App\Foo', null, fn ($foo) => $foo->content === 'Foo 2')), 'filtered by function');

        self::assertSame([
            'foo2' => 'Foo 2',
        ], $getResults($manager->getContents('App\Foo', null, expr('_.content === "Foo 2"'))), 'filtered using an expression');

        self::assertSame([
            'foo2' => 'Foo 2',
        ], $getResults($manager->getContents('App\Foo', null, '_.content === "Foo 2"')), 'filtered using an expression directly provided as string');
    }

    public function testReverseContent(): void
    {
        $manager = new ContentManager(
            ($decoder = $this->prophesize(DecoderInterface::class))->reveal(),
            ($denormalizer = $this->prophesize(DenormalizerInterface::class))->reveal(),
            ($crawlers = $this->prophesize(HtmlCrawlerManagerInterface::class))->reveal(),
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
