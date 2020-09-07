<?php

/*
 * This file is part of the "Tom32i/Content" bundle.
 *
 * @author Thomas Jarrand <thomas.jarrand@gmail.com>
 */

namespace Content\Tests\Unit\DependencyInjection;

use Content\DependencyInjection\Configuration;
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
            'content_dir' => '%kernel.project_dir%/data',
            'build_dir' => '%kernel.project_dir%/site',
        ]]);

        self::assertEquals([
            'content_dir' => '%kernel.project_dir%/data',
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
                ],
                [
                    'src' => '%kernel.project_dir%/public/robots.txt',
                    'dest' => 'robots.txt',
                    'excludes' => [],
                    'fail_if_missing' => true,
                ],
                [
                    'src' => '%kernel.project_dir%/public/missing-file',
                    'dest' => 'missing-file',
                    'excludes' => [],
                    'fail_if_missing' => false,
                ],
            ],
        ] + $this->getDefaultConfig(), $config);
    }

    private function getDefaultConfig(): array
    {
        return [
            'content_dir' => '%kernel.project_dir%/content',
            'build_dir' => '%kernel.project_dir%/build',
            'copy' => [
                [
                    'src' => '%kernel.project_dir%/public',
                    'dest' => '.',
                    'excludes' => ['*.php'],
                    'fail_if_missing' => true,
                ],
            ],
        ];
    }
}
