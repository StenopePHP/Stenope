<?php

/*
 * This file is part of the "Tom32i/Content" bundle.
 *
 * @author Thomas Jarrand <thomas.jarrand@gmail.com>
 */

namespace Content\Tests\Unit\DependencyInjection;

use Content\Builder;
use Content\ContentManager;
use Content\DependencyInjection\ContentExtension;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ParameterBag\EnvPlaceholderParameterBag;

abstract class ContentExtensionTest extends TestCase
{
    const FIXTURES_PATH = __DIR__ . '/../../fixtures/Unit/DependencyInjection/ContentExtension';

    public function testDefaults(): void
    {
        $container = $this->createContainerFromFile('defaults');

        self::assertSame('%kernel.project_dir%/content', $container->getDefinition(ContentManager::class)->getArgument('$path'));
        self::assertSame('%kernel.project_dir%/build', $container->getDefinition(Builder::class)->getArgument('$buildDir'));
        self::assertEquals([
            [
                'src' => '%kernel.project_dir%/public',
                'dest' => '.',
                'excludes' => ['*.php'],
                'fail_if_missing' => true,
            ],
        ], $container->getDefinition(Builder::class)->getArgument('$filesToCopy'));
    }

    public function testDirs(): void
    {
        $container = $this->createContainerFromFile('dirs');

        self::assertSame('PROJECT_DIR/data', $container->getDefinition(ContentManager::class)->getArgument('$path'));
        self::assertSame('PROJECT_DIR/site', $container->getDefinition(Builder::class)->getArgument('$buildDir'));
    }

    public function testCopy(): void
    {
        $container = $this->createContainerFromFile('copy');

        self::assertEquals([
            [
                'src' => 'PROJECT_DIR/public/build',
                'dest' => 'dist',
                'excludes' => ['*.excluded'],
                'fail_if_missing' => true,
            ],
            [
                'src' => 'PROJECT_DIR/public/robots.txt',
                'dest' => 'robots.txt',
                'excludes' => [],
                'fail_if_missing' => true,
            ],
            [
                'src' => 'PROJECT_DIR/public/missing-file',
                'fail_if_missing' => false,
                'excludes' => [],
                'dest' => 'missing-file',
            ],
        ], $container->getDefinition(Builder::class)->getArgument('$filesToCopy'));
    }

    protected function createContainerFromFile(string $file, bool $compile = true): ContainerBuilder
    {
        $container = $this->createContainer();
        $container->registerExtension(new ContentExtension());
        $this->loadFromFile($container, $file);

        $container->getCompilerPassConfig()->setOptimizationPasses([]);
        $container->getCompilerPassConfig()->setRemovingPasses([]);
        $container->getCompilerPassConfig()->setAfterRemovingPasses([]);

        if ($compile) {
            $container->compile();
        }

        return $container;
    }

    abstract protected function loadFromFile(ContainerBuilder $container, string $file);

    protected function createContainer(): ContainerBuilder
    {
        return new ContainerBuilder(new EnvPlaceholderParameterBag([
            'kernel.project_dir' => 'PROJECT_DIR',
        ]));
    }
}
