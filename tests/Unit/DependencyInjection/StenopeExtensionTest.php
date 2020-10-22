<?php

/*
 * This file is part of the "StenopePHP/Stenope" bundle.
 *
 * @author Thomas Jarrand <thomas.jarrand@gmail.com>
 */

namespace Stenope\Tests\Unit\DependencyInjection;

use PHPUnit\Framework\TestCase;
use Stenope\Builder;
use Stenope\DependencyInjection\StenopeExtension;
use Stenope\Provider\Factory\LocalFilesystemProviderFactory;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ParameterBag\EnvPlaceholderParameterBag;

abstract class StenopeExtensionTest extends TestCase
{
    const FIXTURES_PATH = __DIR__ . '/../../fixtures/Unit/DependencyInjection/StenopeExtension';

    public function testDefaults(): void
    {
        $container = $this->createContainerFromFile('defaults');

        self::assertSame('%kernel.project_dir%/build', $container->getDefinition(Builder::class)->getArgument('$buildDir'));
        self::assertEquals([
            [
                'src' => '%kernel.project_dir%/public',
                'dest' => '.',
                'excludes' => ['*.php'],
                'fail_if_missing' => true,
                'ignore_dot_files' => true,
            ],
        ], $container->getDefinition(Builder::class)->getArgument('$filesToCopy'));
    }

    public function testDirs(): void
    {
        $container = $this->createContainerFromFile('dirs');

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
                'ignore_dot_files' => false,
            ],
            [
                'src' => 'PROJECT_DIR/public/robots.txt',
                'dest' => 'robots.txt',
                'excludes' => [],
                'fail_if_missing' => true,
                'ignore_dot_files' => true,
            ],
            [
                'src' => 'PROJECT_DIR/public/missing-file',
                'fail_if_missing' => false,
                'excludes' => [],
                'dest' => 'missing-file',
                'ignore_dot_files' => true,
            ],
        ], $container->getDefinition(Builder::class)->getArgument('$filesToCopy'));
    }

    public function testProviders(): void
    {
        $container = $this->createContainerFromFile('providers');

        $filesProviderFactory = $container->getDefinition('stenope.provider.files.Foo\Bar');
        self::assertEquals(LocalFilesystemProviderFactory::TYPE, $filesProviderFactory->getArgument('$type'));
        self::assertEquals([
            'class' => 'Foo\Bar',
            'path' => 'PROJECT_DIR/foo/bar',
            'depth' => '< 2',
            'patterns' => ['*.md', '*.html'],
            'excludes' => ['excluded.md'],
        ], $filesProviderFactory->getArgument('$config'));

        $customProviderFactory = $container->getDefinition('stenope.provider.custom.Foo\Custom');
        self::assertEquals('custom', $customProviderFactory->getArgument('$type'));
        self::assertEquals([
            'class' => 'Foo\Custom',
            'custom_config_key' => 'custom_value',
            'custom_sequence' => ['custom_sequence_value_1', 'custom_sequence_value_2'],
        ], $customProviderFactory->getArgument('$config'));
    }

    protected function createContainerFromFile(string $file, bool $compile = true): ContainerBuilder
    {
        $container = $this->createContainer();
        $container->registerExtension(new StenopeExtension());
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
