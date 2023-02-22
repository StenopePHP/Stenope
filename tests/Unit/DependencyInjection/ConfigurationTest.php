<?php

/*
 * This file is part of the "StenopePHP/Stenope" bundle.
 *
 * @author Thomas Jarrand <thomas.jarrand@gmail.com>
 */

namespace Stenope\Bundle\Tests\Unit\DependencyInjection;

use PHPUnit\Framework\TestCase;
use Stenope\Bundle\DependencyInjection\Configuration;
use Stenope\Bundle\Provider\Factory\LocalFilesystemProviderFactory;
use Symfony\Component\Config\Definition\Processor;

class ConfigurationTest extends TestCase
{
    public function testDefaultConfig(): void
    {
        $processor = new Processor();
        $config = $processor->processConfiguration(new Configuration(), [[]]);

        self::assertEquals($this->getDefaultConfig(), $config);
    }

    public function testDirsConfig(): void
    {
        $processor = new Processor();
        $config = $processor->processConfiguration(new Configuration(), [[
            'build_dir' => '%kernel.project_dir%/site',
        ]]);

        self::assertEquals([
            'build_dir' => '%kernel.project_dir%/site',
        ] + $this->getDefaultConfig(), $config);
    }

    public function testCopyConfig(): void
    {
        $processor = new Processor();
        $config = $processor->processConfiguration(new Configuration(), [[
            'copy' => [
                ['src' => '%kernel.project_dir%/public/build', 'dest' => 'dist', 'excludes' => ['*.excluded']],
                '%kernel.project_dir%/public/robots.txt',
                ['src' => '%kernel.project_dir%/public/missing-file', 'fail_if_missing' => false],
            ],
        ]]);

        self::assertEquals([
            'copy' => [
                [
                    'src' => '%kernel.project_dir%/public/build',
                    'dest' => 'dist',
                    'excludes' => ['*.excluded'],
                    'fail_if_missing' => true,
                    'ignore_dot_files' => true,
                ],
                [
                    'src' => '%kernel.project_dir%/public/robots.txt',
                    'dest' => 'robots.txt',
                    'excludes' => [],
                    'fail_if_missing' => true,
                    'ignore_dot_files' => true,
                ],
                [
                    'src' => '%kernel.project_dir%/public/missing-file',
                    'dest' => 'missing-file',
                    'excludes' => [],
                    'fail_if_missing' => false,
                    'ignore_dot_files' => true,
                ],
            ],
        ] + $this->getDefaultConfig(), $config);
    }

    public function testResolveLinksConfig(): void
    {
        $processor = new Processor();
        $config = $processor->processConfiguration(new Configuration(), [[
            'resolve_links' => [
                'Foo\Bar' => ['route' => 'show_bar', 'slug' => 'bar'],
                'Foo\Baz' => ['route' => 'show_baz', 'slug' => 'slug'],
            ],
        ]]);

        self::assertEquals([
            'resolve_links' => [
                'Foo\Bar' => ['route' => 'show_bar', 'slug' => 'bar'],
                'Foo\Baz' => ['route' => 'show_baz', 'slug' => 'slug'],
            ],
        ] + $this->getDefaultConfig(), $config);
    }

    /**
     * @dataProvider providerProvidersConfig
     */
    public function testProvidersConfig(array $config, array $expected): void
    {
        $processor = new Processor();
        $config = $processor->processConfiguration(new Configuration(), [$config]);

        self::assertEquals($expected + $this->getDefaultConfig(), $config);
    }

    public function providerProvidersConfig(): iterable
    {
        yield 'minimal "files" config' => [
            'config' => [
                'providers' => [
                    'Foo\Bar' => [
                        'type' => LocalFilesystemProviderFactory::TYPE,
                        'config' => [
                            'path' => 'foo/bar',
                        ],
                    ],
                ],
            ],
            'expected' => [
                'providers' => [
                    'Foo\Bar' => [
                        'type' => LocalFilesystemProviderFactory::TYPE,
                        'config' => [
                            'path' => 'foo/bar',
                            'depth' => null,
                            'patterns' => ['*'],
                            'excludes' => [],
                        ],
                    ],
                ],
            ],
        ];

        yield 'full "files" config' => [
            'config' => [
                'providers' => [
                    'Foo\Bar' => [
                        'type' => LocalFilesystemProviderFactory::TYPE,
                        'config' => [
                            'path' => 'foo/bar',
                            'depth' => '< 2',
                            'patterns' => ['*.md', '*.html'],
                            'excludes' => ['excluded.md'],
                        ],
                    ],
                ],
            ],
            'expected' => [
                'providers' => [
                    'Foo\Bar' => [
                        'type' => LocalFilesystemProviderFactory::TYPE,
                        'config' => [
                            'path' => 'foo/bar',
                            'depth' => '< 2',
                            'patterns' => ['*.md', '*.html'],
                            'excludes' => ['excluded.md'],
                        ],
                    ],
                ],
            ],
        ];

        yield 'normalized "files" config' => [
            'config' => [
                'providers' => [
                    'Foo\Bar' => [
                        'type' => LocalFilesystemProviderFactory::TYPE,
                        'config' => [
                            'path' => 'foo/bar',
                            'patterns' => '*.md',
                            'excludes' => 'excluded.md',
                        ],
                    ],
                ],
            ],
            'expected' => [
                'providers' => [
                    'Foo\Bar' => [
                        'type' => LocalFilesystemProviderFactory::TYPE,
                        'config' => [
                            'path' => 'foo/bar',
                            'depth' => null,
                            'patterns' => ['*.md'],
                            'excludes' => ['excluded.md'],
                        ],
                    ],
                ],
            ],
        ];

        yield 'custom provider config' => [
            'config' => [
                'providers' => [
                    'Foo\Bar' => [
                        'type' => 'custom',
                        'config' => [
                            'foo' => 'bar',
                            'baz' => 'qux',
                        ],
                    ],
                ],
            ],
            'expected' => [
                'providers' => [
                    'Foo\Bar' => [
                        'type' => 'custom',
                        'config' => [
                            'foo' => 'bar',
                            'baz' => 'qux',
                        ],
                    ],
                ],
            ],
        ];

        yield 'shortcut config' => [
            'config' => [
                'providers' => [
                    'Foo\Bar' => [
                        'path' => 'foo/bar',
                        'patterns' => '*.md',
                        'excludes' => 'excluded.md',
                    ],
                ],
            ],
            'expected' => [
                'providers' => [
                    'Foo\Bar' => [
                        'type' => LocalFilesystemProviderFactory::TYPE,
                        'config' => [
                            'path' => 'foo/bar',
                            'depth' => null,
                            'patterns' => ['*.md'],
                            'excludes' => ['excluded.md'],
                        ],
                    ],
                ],
            ],
        ];
        yield 'shortcut "files" config' => [
            'config' => [
                'providers' => [
                    'Foo\Bar' => 'foo/bar',
                ],
            ],
            'expected' => [
                'providers' => [
                    'Foo\Bar' => [
                        'type' => LocalFilesystemProviderFactory::TYPE,
                        'config' => [
                            'path' => 'foo/bar',
                            'depth' => null,
                            'patterns' => ['*'],
                            'excludes' => [],
                        ],
                    ],
                ],
            ],
        ];
    }

    private function getDefaultConfig(): array
    {
        return [
            'build_dir' => '%kernel.project_dir%/build',
            'copy' => [
                [
                    'src' => '%kernel.project_dir%/public',
                    'dest' => '.',
                    'excludes' => ['*.php'],
                    'fail_if_missing' => true,
                    'ignore_dot_files' => true,
                ],
            ],
            'providers' => [],
            'resolve_links' => [],
            'shared_html_crawlers' => false,
            'processors' => [
                'enabled' => true,
                'content_property' => 'content',
                'slug' => [
                    'enabled' => true,
                    'property' => 'slug',
                ],
                'assets' => [
                    'enabled' => true,
                ],
                'resolve_content_links' => [
                    'enabled' => true,
                ],
                'external_links' => [
                    'enabled' => true,
                ],
                'anchors' => [
                    'enabled' => true,
                    'selector' => 'h1, h2, h3, h4, h5',
                ],
                'html_title' => [
                    'enabled' => true,
                    'property' => 'title',
                ],
                'html_elements_ids' => [
                    'enabled' => true,
                ],
                'code_highlight' => [
                    'enabled' => true,
                ],
                'toc' => [
                    'enabled' => true,
                    'property' => 'tableOfContent',
                    'min_depth' => 2,
                    'max_depth' => 6,
                ],
                'last_modified' => [
                    'enabled' => true,
                    'property' => 'lastModified',
                    'git' => [
                        'enabled' => true,
                        'path' => 'git',
                    ],
                ],
            ],
        ];
    }
}
