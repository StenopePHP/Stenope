<?php

/*
 * This file is part of the "StenopePHP/Stenope" bundle.
 *
 * @author Thomas Jarrand <thomas.jarrand@gmail.com>
 * @author Maxime Steinhausser <maxime.steinhausser@gmail.com>
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
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\Serializer\Encoder\DecoderInterface;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

class ContentManagerTest extends TestCase
{
    use ProphecyTrait;

    /** The build id the helpers pass, unless a test varies it on purpose. */
    private const DEFAULT_BUILD_ID = 'build-1';

    private ArrayAdapter $cachePool;
    private CountingDecoder $decoder;

    protected function setUp(): void
    {
        $this->cachePool = new ArrayAdapter();
        $this->decoder = new CountingDecoder();
    }

    public function testGetContents(): void
    {
        $manager = new ContentManager(
            ($decoder = $this->prophesize(DecoderInterface::class))->reveal(),
            ($denormalizer = $this->prophesize(DenormalizerInterface::class))->reveal(),
            $this->prophesize(HtmlCrawlerManagerInterface::class)->reveal(),
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

    public function testContentIsLoadedOnceAcrossRequests(): void
    {
        $first = $this->requestContent();
        $second = $this->requestContent();

        self::assertSame(
            expected: 1,
            actual: $this->decoder->loads,
            message: sprintf(
                'The second request must read the content from the cache pool; expected 1 load for 2 requests of the same content, got %d.',
                $this->decoder->loads,
            ),
        );

        self::assertEquals(
            expected: $first,
            actual: $second,
            message: 'What comes back from the cache pool must equal what was loaded.',
        );
    }

    public function testEditedContentMissesTheCache(): void
    {
        // The key covers the raw file, front matter included — not just the text below it.
        $revisions = [
            'original' => "---\ntitle: Foo\n---\nFoo 1",
            'edited text' => "---\ntitle: Foo\n---\nFoo 1 edited",
            'edited front matter' => "---\ntitle: Foo v2\n---\nFoo 1 edited",
        ];

        foreach ($revisions as $rawContent) {
            $this->requestContent(ContentFactory::create(rawContent: $rawContent));
        }

        self::assertSame(
            expected: \count($revisions),
            actual: $this->decoder->loads,
            message: sprintf(
                'Every revision of the file must produce its own cache key; expected %d loads for %s, got %d.',
                \count($revisions),
                implode(', ', array_keys($revisions)),
                $this->decoder->loads,
            ),
        );
    }

    public function testEditedContentReturnsFreshContent(): void
    {
        $this->requestContent();
        $edited = $this->requestContent(ContentFactory::edited());

        self::assertSame(
            expected: ContentFactory::EDITED_CONTENT,
            actual: $edited->decoded,
            message: 'An edited content must be read again, not served from the cache pool.',
        );
    }

    public function testANewLastModifiedDateDoesNotInvalidateTheCache(): void
    {
        $dates = ['2021-06-14', '2026-08-20'];

        foreach ($dates as $date) {
            $this->requestContent(ContentFactory::create(lastModified: $date));
        }

        // The content is unchanged, only its date is: a checkout, a copy or a deploy does that to every file.
        self::assertSame(
            expected: 1,
            actual: $this->decoder->loads,
            message: sprintf(
                'A new last modified date alone must not invalidate; expected 1 load for the same content dated %s, got %d.',
                implode(' and then ', $dates),
                $this->decoder->loads,
            ),
        );
    }

    public function testClosureCriteriaDoNotBypassTheCache(): void
    {
        $this->requestContents(filterBy: static fn (object $content): bool => true);
        $this->requestContents(filterBy: static fn (object $content): bool => true);

        // Sorting and filtering happen after loading, so the criteria never reach a cache key.
        self::assertSame(
            expected: 1,
            actual: $this->decoder->loads,
            message: sprintf(
                'A closure criterion must not prevent caching; expected 1 load for 2 requests of the same content, got %d.',
                $this->decoder->loads,
            ),
        );
    }

    public function testDifferentContentsDoNotShareACacheEntry(): void
    {
        $contents = [ContentFactory::create(), ContentFactory::other(), ContentFactory::create()];
        $slugs = array_map(static fn (Content $content): string => $content->getSlug(), $contents);

        foreach ($contents as $content) {
            $this->requestContent($content);
        }

        // One load per distinct content: the third request finds the entry the first one wrote.
        self::assertSame(
            expected: 2,
            actual: $this->decoder->loads,
            message: sprintf(
                'Each content must get its own cache entry; expected 2 loads for the contents %s, got %d.',
                implode(', ', $slugs),
                $this->decoder->loads,
            ),
        );
    }

    public function testContentsOfDifferentTypesDoNotShareACacheEntry(): void
    {
        // Same slug throughout — two content classes may well use one, so only the type separates them.
        $contents = [ContentFactory::create(), ContentFactory::ofAnotherType(), ContentFactory::create()];
        $types = array_map(static fn (Content $content): string => $content->getType(), $contents);

        foreach ($contents as $content) {
            $this->requestContent($content);
        }

        self::assertSame(
            expected: 2,
            actual: $this->decoder->loads,
            message: sprintf(
                'Each type must get its own cache entry; expected 2 loads for slug "%s" as %s, got %d.',
                ContentFactory::SLUG,
                implode(', ', $types),
                $this->decoder->loads,
            ),
        );
    }

    public function testContentsOfDifferentFormatsDoNotShareACacheEntry(): void
    {
        // Same bytes throughout: foo.md and foo.html may hold the very same text and still decode differently.
        $contents = [ContentFactory::create(), ContentFactory::inAnotherFormat(), ContentFactory::create()];
        $formats = array_map(static fn (Content $content): string => $content->getFormat(), $contents);

        foreach ($contents as $content) {
            $this->requestContent($content);
        }

        self::assertSame(
            expected: 2,
            actual: $this->decoder->loads,
            message: sprintf(
                'Each format must get its own cache entry; expected 2 loads for the same content as %s, got %d.',
                implode(', ', $formats),
                $this->decoder->loads,
            ),
        );
    }

    /**
     * @dataProvider provideSlugData
     */
    public function testAnySlugProducesAUsableCacheKey(string $slug): void
    {
        $this->requestContent(ContentFactory::create(slug: $slug));
        $this->requestContent(ContentFactory::create(slug: $slug));

        self::assertSame(
            expected: 1,
            actual: $this->decoder->loads,
            message: sprintf('Slug "%s" must still produce a usable cache key.', $slug),
        );
    }

    public static function provideSlugData(): iterable
    {
        yield 'plain' => [ContentFactory::SLUG];
        yield 'reserved by PSR-6' => ['a{b}c(d)e/f\\g@h:i'];
        yield 'unicode' => ['über-café-日本語'];
        yield 'spaces and dots' => ['hello world.v2'];
    }

    public function testARebuiltContainerInvalidatesTheCache(): void
    {
        $builds = [self::DEFAULT_BUILD_ID, 'build-2'];

        foreach ($builds as $build) {
            $this->requestContent(cacheVersion: $build);
        }

        // Processors and their configuration are not in the key; a rebuilt container says they may have changed.
        self::assertSame(
            expected: 2,
            actual: $this->decoder->loads,
            message: sprintf(
                'A new container build id must invalidate; expected 2 loads for the same content under %s, got %d.',
                implode(' and then ', $builds),
                $this->decoder->loads,
            ),
        );
    }

    /**
     * Documents a limit rather than a feature: the entry is served although what a processor read
     * has changed since. Resolved content links and asset URLs behave the same way.
     */
    public function testCacheKeyIgnoresWhatProcessorsReadElsewhere(): void
    {
        $elsewhere = 'before';
        // By reference: without it the closure keeps 'before' and the test cannot fail.
        $processor = static function (array &$data, Content $content) use (&$elsewhere): void {
            $data['decorated'] = $elsewhere;
        };

        $this->requestContent(processors: [$processor]);
        $elsewhere = 'after';
        $again = $this->requestContent(processors: [$processor]);

        // This is why a link resolved against another content keeps the URL it had when the entry was written.
        self::assertSame(
            expected: 'before',
            actual: $again->decorated,
            message: 'A cached content must keep what a processor read outside of it when the entry was written.',
        );
    }

    public function testWorksWithoutACachePool(): void
    {
        // The null branch exists so the new constructor parameter does not break direct instantiation.
        self::assertSame(
            expected: ContentFactory::RAW_CONTENT,
            actual: $this->requestContent(cached: false)->decoded,
            message: 'Without a cache pool the decoded content must still reach the caller.',
        );

        self::assertSame(
            expected: 1,
            actual: $this->decoder->loads,
            message: sprintf(
                'Without a cache pool the content must still be loaded; expected 1 load for the single request, got %d.',
                $this->decoder->loads,
            ),
        );
    }

    /** One request against a fresh content manager; only the cache pool is shared between them. */
    private function requestContent(?Content $content = null, bool $cached = true, string $cacheVersion = self::DEFAULT_BUILD_ID, iterable $processors = []): object
    {
        $content ??= ContentFactory::create();

        return $this->createContentManager($content, $cached, $cacheVersion, $processors)->getContent(
            type: $content->getType(),
            id: $content->getSlug(),
        );
    }

    /** One request listing contents, against a fresh content manager too. */
    private function requestContents(mixed $sortBy = null, mixed $filterBy = null): array
    {
        return $this->createContentManager()->getContents(type: ContentFactory::TYPE, sortBy: $sortBy, filterBy: $filterBy);
    }

    /** A content manager serving a single content. */
    private function createContentManager(?Content $content = null, bool $cached = true, string $cacheVersion = self::DEFAULT_BUILD_ID, iterable $processors = []): ContentManager
    {
        $content ??= ContentFactory::create();

        return new ContentManager(
            decoder: $this->decoder,
            denormalizer: $this->passThroughDenormalizer($content),
            crawlers: $this->prophesize(HtmlCrawlerManagerInterface::class)->reveal(),
            contentProviders: [$this->providerServing($content)],
            processors: $processors,
            cachePool: $cached ? $this->cachePool : null,
            cacheVersion: $cacheVersion,
        );
    }

    /** Answers with the given content, by slug and in listings alike. */
    private function providerServing(Content $content): ContentProviderInterface
    {
        $provider = $this->prophesize(ContentProviderInterface::class);
        $provider->supports($content->getType())->willReturn(true);
        $provider->getContent($content->getSlug())->willReturn($content);
        $provider->listContents()->willReturn([$content]);

        return $provider->reveal();
    }

    /** Hands the decoded array back as an object, so ['decoded' => 'Foo 1'] gets a ->decoded. */
    private function passThroughDenormalizer(Content $content): DenormalizerInterface
    {
        $denormalizer = $this->prophesize(DenormalizerInterface::class);
        $denormalizer
            ->denormalize(Argument::type('array'), $content->getType(), Argument::any(), Argument::any())
            ->will(fn (array $args) => (object) $args[0])
        ;

        return $denormalizer->reveal();
    }
}

final class ContentFactory
{
    public const TYPE = 'App\Foo';
    public const OTHER_TYPE = 'App\Bar';
    public const SLUG = 'foo1';
    public const RAW_CONTENT = 'Foo 1';
    public const EDITED_CONTENT = self::RAW_CONTENT . ' edited';
    public const OTHER_SLUG = 'bar1';
    public const OTHER_CONTENT = 'Bar 1';
    public const LAST_MODIFIED = '2021-06-14';
    public const FORMAT = 'markdown';
    public const OTHER_FORMAT = 'html';

    public static function create(
        string $rawContent = self::RAW_CONTENT,
        ?string $lastModified = self::LAST_MODIFIED,
        string $slug = self::SLUG,
        string $type = self::TYPE,
        string $format = self::FORMAT,
    ): Content {
        return new Content(
            slug: $slug,
            type: $type,
            rawContent: $rawContent,
            format: $format,
            lastModified: $lastModified !== null ? new \DateTimeImmutable($lastModified) : null,
        );
    }

    /** The same content after a save: new text, and the newer date the filesystem stamps on it. */
    public static function edited(): Content
    {
        return self::create(
            rawContent: self::EDITED_CONTENT,
            lastModified: self::LAST_MODIFIED . ' +1 day',
        );
    }

    /** A content of another class, with the same slug — only the type differs from create(). */
    public static function ofAnotherType(): Content
    {
        return self::create(type: self::OTHER_TYPE);
    }

    /** The same bytes, decoded as another format — only the format differs from create(). */
    public static function inAnotherFormat(): Content
    {
        return self::create(format: self::OTHER_FORMAT);
    }

    /** A second, unrelated content — it must not share a cache entry with the first. */
    public static function other(): Content
    {
        return self::create(
            rawContent: self::OTHER_CONTENT,
            slug: self::OTHER_SLUG,
        );
    }
}

/** Counts what a cached content never reaches: every load ends up here, every cache hit does not. */
final class CountingDecoder implements DecoderInterface
{
    public int $loads = 0;

    public function decode(string $data, string $format, array $context = []): array
    {
        ++$this->loads;

        return ['decoded' => $data];
    }

    public function supportsDecoding(string $format): bool
    {
        return true;
    }
}

interface ContentManagerAwareProcessorInterface extends ProcessorInterface, ContentManagerAwareInterface
{
}
