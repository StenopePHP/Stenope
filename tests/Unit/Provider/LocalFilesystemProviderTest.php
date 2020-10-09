<?php

/*
 * This file is part of the "Tom32i/Content" bundle.
 *
 * @author Thomas Jarrand <thomas.jarrand@gmail.com>
 */

namespace Content\Tests\Unit\Provider;

use Content\Content;
use Content\Provider\LocalFilesystemProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\VarDumper\Cloner\Stub;
use Symfony\Component\VarDumper\Dumper\CliDumper;
use Symfony\Component\VarDumper\Test\VarDumperTestTrait;

class LocalFilesystemProviderTest extends TestCase
{
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

        $this->assertDumpEquals(<<<'DUMP'
            Content\Content {
              -slug: "foo"
              -rawContent: """
                ---\n
                title: Foo\n
                ---\n
                \n
                Extend doesn’t silently capture any sinner — but the scholar is what remains.\n
                """
              -format: "markdown"
              -lastModified: & 📅 DATE
              -createdAt: null
            }
            DUMP,
            $provider->getContent('foo'),
        );

        self::assertSame('bar/bar', $provider->getContent('bar/bar')->getSlug());
//        self::assertSame('bar/baz/baz', $provider->getContent('bar/baz/baz')->getSlug());
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
    }
}
