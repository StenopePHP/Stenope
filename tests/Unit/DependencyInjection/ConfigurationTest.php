<?php

/*
 * This file is part of the "Tom32i/Content" bundle.
 *
 * @author Thomas Jarrand <thomas.jarrand@gmail.com>
 */

namespace Content\Tests\Unit\DependencyInjection;

use Content\DependencyInjection\Configuration;
use Content\Provider\Factory\LocalFilesystemProviderFactory;
use PHPUnit\Framework\TestCase;
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
        ];
    }
}
