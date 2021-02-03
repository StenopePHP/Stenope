<?php

/*
 * This file is part of the "StenopePHP/Stenope" bundle.
 *
 * @author Thomas Jarrand <thomas.jarrand@gmail.com>
 */

namespace Stenope\Bundle\Tests\Unit\Provider;

use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Stenope\Bundle\Content;
use Stenope\Bundle\Provider\LocalFilesystemProvider;
use Stenope\Bundle\ReverseContent\Context;
use Stenope\Bundle\ReverseContent\RelativeLinkContext;
use Symfony\Component\VarDumper\Cloner\Stub;
use Symfony\Component\VarDumper\Dumper\CliDumper;
use Symfony\Component\VarDumper\Test\VarDumperTestTrait;

class LocalFilesystemProviderTest extends TestCase
{
    use ProphecyTrait;
    use VarDumperTestTrait {
        setUpVarDumper as setUpVarDumperParent;
    }

    private const FIXTURES_DIR = FIXTURES_DIR . '/Unit/Provider/LocalFilesystemProvider';

    protected function setUpVarDumper(array $casters, int $flags = null): void
    {
        $this->setUpVarDumperParent(
            $casters,
            $flags ?? CliDumper::DUMP_LIGHT_ARRAY | CliDumper::DUMP_TRAILING_COMMA | CliDumper::DUMP_COMMA_SEPARATOR
        );
    }

    public function setUp(): void
    {
        $this->setUpVarDumper([
            \DateTimeInterface::class => static function (\DateTimeInterface $d, array $a, Stub $s) {
                $s->class = '📅 DATE';
                $s->type = Stub::TYPE_REF;

                return $a;
            },
        ]);
    }

    public function testSupport(): void
    {
        $provider = new LocalFilesystemProvider('App\Foo', self::FIXTURES_DIR . '/content/foo');

        self::assertTrue($provider->supports('App\Foo'));
        self::assertFalse($provider->supports('App\Bar'));
    }

    public function testGetContent(): void
    {
        $provider = new LocalFilesystemProvider(
            'App\Foo',
            self::FIXTURES_DIR . '/content/foo',
        );
        $basePath = realpath(self::FIXTURES_DIR);

        $this->assertDumpEquals(<<<DUMP
            Stenope\Bundle\Content {
              -slug: "foo"
              -type: "App\Foo"
              -rawContent: """
                ---\\n
                title: Foo\\n
                ---\\n
                \\n
                Extend doesn’t silently capture any sinner — but the scholar is what remains.\\n
                """
              -format: "markdown"
              -lastModified: & 📅 DATE
              -createdAt: null
              -metadata: [
                "path" => "$basePath/content/foo/foo.md",
                "provider" => "files",
              ]
            }
            DUMP,
            $provider->getContent('foo'),
        );

        self::assertSame('bar/bar', $provider->getContent('bar/bar')->getSlug());
    }

    /**
     * @dataProvider provideListContentsData
     */
    public function testListContents(LocalFilesystemProvider $provider, array $expected): void
    {
        self::assertEqualsCanonicalizing($expected, array_map(
            fn (Content $c) => sprintf('%s (%s)', $c->getSlug(), $c->getFormat()),
            iterator_to_array($provider->listContents())
        ));
    }

    public function testReverse(): void
    {
        $provider = new LocalFilesystemProvider(
            'App\Foo',
            $basePath = self::FIXTURES_DIR . '/content/foo',
        );

        self::assertInstanceOf(Content::class, $resolved = $provider->reverse(new RelativeLinkContext(
            [
                'path' => "$basePath/bar/baz/baz.md",
                'provider' => 'files',
            ],
            '../../foo.md',
        )), 'target found');
        self::assertSame('foo', $resolved->getSlug());

        self::assertNull($provider->reverse(new RelativeLinkContext(
            [
                'path' => "$basePath/bar/baz/baz.md",
                'provider' => 'files',
            ],
            '../non-existing-file.md',
        )), 'target not found');

        self::assertNull($provider->reverse(new RelativeLinkContext(
            [
                'path' => "$basePath/bar/baz/baz.md",
                'provider' => 'not-files',
            ],
            '../non-existing-file.md',
        )), 'current content not provided by a filesystem provider');

        self::assertNull($provider->reverse($this->prophesize(Context::class)->reveal()), 'Not a relative link context');
    }

    public function provideListContentsData(): iterable
    {
        yield 'default config' => [
            new LocalFilesystemProvider(
                'App\Foo',
                self::FIXTURES_DIR . '/content/foo',
            ),
            [
              'foo (markdown)',
              'foo2 (markdown)',
              'foo3 (html)',
              'bar/bar (markdown)',
              'bar/bar2 (markdown)',
              'bar/baz/baz (markdown)',
            ],
        ];

        yield 'config with depth' => [
            new LocalFilesystemProvider(
                'App\Foo',
                self::FIXTURES_DIR . '/content/foo',
                '< 1',
            ),
            [
              'foo (markdown)',
              'foo2 (markdown)',
              'foo3 (html)',
            ],
        ];

        yield 'config with exclude & patterns' => [
            new LocalFilesystemProvider(
                'App\Foo',
                self::FIXTURES_DIR . '/content/foo',
                null,
                ['foo2.md', 'bar/*2.md'],
                ['*.md'],
            ),
            [
              'foo (markdown)',
              'bar/bar (markdown)',
              'bar/baz/baz (markdown)',
            ],
        ];

        yield 'config excluding dir as glob' => [
            new LocalFilesystemProvider(
                'App\Foo',
                self::FIXTURES_DIR . '/content/excluded_dirs',
                null,
                ['bar/*'],
                ['*.md'],
            ),
            [
                'bar (markdown)',
                'foo/bar (markdown)',
                'foo/bar/baz (markdown)',
            ],
        ];

        // This one cannot be resolved until https://github.com/symfony/symfony/issues/28158 is.
        // If one really needs to exclude a dir but not subdirs with the same name, they must use the glob pattern
        // as in the previous test case sample, despite it may have a big performances impact
        //yield 'config excluding explicit dir (not as a glob)' => [
        //    new LocalFilesystemProvider(
        //        'App\Foo',
        //        self::FIXTURES_DIR . '/content/excluded_dirs',
        //        null,
        //        ['bar/'],
        //        ['*.md'],
        //    ),
        //    [
        //        'bar (markdown)',
        //        'foo/bar (markdown)',
        //        'foo/bar/baz (markdown)',
        //    ],
        //];
    }
}
