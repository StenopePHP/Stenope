<?php

/*
 * This file is part of the "StenopePHP/Stenope" bundle.
 *
 * @author Thomas Jarrand <thomas.jarrand@gmail.com>
 */

namespace Stenope\Bundle\Tests\Unit\DependencyInjection;

use PHPUnit\Framework\TestCase;
use Stenope\Bundle\Builder;
use Stenope\Bundle\DependencyInjection\StenopeExtension;
use Stenope\Bundle\Processor\AssetsProcessor;
use Stenope\Bundle\Processor\CodeHighlightProcessor;
use Stenope\Bundle\Processor\ExtractTitleFromHtmlContentProcessor;
use Stenope\Bundle\Processor\HtmlAnchorProcessor;
use Stenope\Bundle\Processor\HtmlExternalLinksProcessor;
use Stenope\Bundle\Processor\HtmlIdProcessor;
use Stenope\Bundle\Processor\LastModifiedProcessor;
use Stenope\Bundle\Processor\ResolveContentLinksProcessor;
use Stenope\Bundle\Processor\SlugProcessor;
use Stenope\Bundle\Processor\TableOfContentProcessor;
use Stenope\Bundle\Provider\Factory\LocalFilesystemProviderFactory;
use Stenope\Bundle\Routing\ContentUrlResolver;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ParameterBag\EnvPlaceholderParameterBag;
use Symfony\Component\DependencyInjection\Reference;

abstract class StenopeExtensionTest extends TestCase
{
    public const FIXTURES_PATH = __DIR__ . '/../../fixtures/Unit/DependencyInjection/StenopeExtension';

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

    public function testResolveLinks(): void
    {
        $container = $this->createContainerFromFile('resolve_links');

        $resolver = $container->getDefinition(ContentUrlResolver::class);
        self::assertCount(1, $routes = $resolver->getArgument('$routes'));
        self::assertInstanceOf(Reference::class, $ref = $routes['Foo\Bar']);
        self::assertSame(['foo_bar', 'foo', []], $container->getDefinition($ref)->getArguments());
    }

    public function testDisabledProcessors(): void
    {
        $container = $this->createContainerFromFile('disabled_processors');

        self::assertCount(0, $container->findTaggedServiceIds('stenope.processor'), 'Default built-in processors are not registered');
    }

    public function testDefaultProcessors(): void
    {
        $container = $this->createContainerFromFile('defaults');

        self::assertNotCount(0, $container->findTaggedServiceIds('stenope.processor'), 'Default built-in processors are registered');

        // SlugProcessor
        self::assertSame('slug', $container->getDefinition(SlugProcessor::class)->getArgument('$property'));

        // AssetsProcessor
        self::assertSame('content', $container->getDefinition(AssetsProcessor::class)->getArgument('$property'));

        // ResolveContentLinksProcessor
        self::assertSame('content', $container->getDefinition(ResolveContentLinksProcessor::class)->getArgument('$property'));

        // HtmlExternalLinksProcessor
        self::assertSame('content', $container->getDefinition(HtmlExternalLinksProcessor::class)->getArgument('$property'));

        // HtmlAnchorProcessor
        $def = $container->getDefinition(HtmlAnchorProcessor::class);
        self::assertSame('h1, h2, h3, h4, h5', $def->getArgument('$selector'));
        self::assertSame('content', $def->getArgument('$property'));

        // ExtractTitleFromHtmlContentProcessor
        $def = $container->getDefinition(ExtractTitleFromHtmlContentProcessor::class);
        self::assertSame('title', $def->getArgument('$titleProperty'));
        self::assertSame('content', $def->getArgument('$contentProperty'));

        // HtmlIdProcessor
        self::assertSame('content', $container->getDefinition(HtmlIdProcessor::class)->getArgument('$property'));

        // CodeHighlightProcessor
        self::assertSame('content', $container->getDefinition(CodeHighlightProcessor::class)->getArgument('$property'));

        // TableOfContentProcessor
        $def = $container->getDefinition(TableOfContentProcessor::class);
        self::assertSame('tableOfContent', $def->getArgument('$tableOfContentProperty'));
        self::assertSame(2, $def->getArgument('$minDepth'));
        self::assertSame(6, $def->getArgument('$maxDepth'));
        self::assertSame('content', $def->getArgument('$contentProperty'));

        // LastModifiedProcessor
        $def = $container->getDefinition(LastModifiedProcessor::class);
        self::assertSame('lastModified', $def->getArgument('$property'));
        self::assertSame('git', $def->getArgument('$gitLastModified')->getArgument('$gitPath'));
    }

    /**
     * @dataProvider provideProcessorsConfig
     */
    public function testProcessorsConfig(string $configName, callable $expectations): void
    {
        $container = $this->createContainerFromFile("processors/$configName");

        $expectations($container);
    }

    public function provideProcessorsConfig(): iterable
    {
        yield 'slug disabled' => ['slug_disabled', function (ContainerBuilder $container): void {
            self::assertFalse($container->hasDefinition(SlugProcessor::class));
        }];

        yield 'slug property' => ['slug_property', function (ContainerBuilder $container): void {
            self::assertSame('id', $container->getDefinition(SlugProcessor::class)->getArgument('$property'));
        }];

        yield 'assets disabled' => ['assets_disabled', function (ContainerBuilder $container): void {
            self::assertFalse($container->hasDefinition(AssetsProcessor::class));
        }];

        yield 'resolve_content_links disabled' => ['resolve_content_links_disabled', function (ContainerBuilder $container): void {
            self::assertFalse($container->hasDefinition(ResolveContentLinksProcessor::class));
        }];

        yield 'external_links disabled' => ['external_links_disabled', function (ContainerBuilder $container): void {
            self::assertFalse($container->hasDefinition(HtmlExternalLinksProcessor::class));
        }];

        yield 'anchors disabled' => ['anchors_disabled', function (ContainerBuilder $container): void {
            self::assertFalse($container->hasDefinition(HtmlAnchorProcessor::class));
        }];

        yield 'anchors selector' => ['anchors_selector', function (ContainerBuilder $container): void {
            self::assertSame('h1, h2, h4', $container->getDefinition(HtmlAnchorProcessor::class)->getArgument('$selector'));
        }];

        yield 'html_title selector' => ['html_title_disabled', function (ContainerBuilder $container): void {
            self::assertFalse($container->hasDefinition(ExtractTitleFromHtmlContentProcessor::class));
        }];

        yield 'html_title property' => ['html_title_property', function (ContainerBuilder $container): void {
            self::assertSame('documentName', $container->getDefinition(ExtractTitleFromHtmlContentProcessor::class)->getArgument('$titleProperty'));
        }];

        yield 'html_elements_ids disabled' => ['html_elements_ids_disabled', function (ContainerBuilder $container): void {
            self::assertFalse($container->hasDefinition(HtmlIdProcessor::class));
        }];

        yield 'code_highlight disabled' => ['code_highlight_disabled', function (ContainerBuilder $container): void {
            self::assertFalse($container->hasDefinition(CodeHighlightProcessor::class));
        }];

        yield 'toc disabled' => ['toc_disabled', function (ContainerBuilder $container): void {
            self::assertFalse($container->hasDefinition(TableOfContentProcessor::class));
        }];

        yield 'toc config' => ['toc_config', function (ContainerBuilder $container): void {
            self::assertNotNull($def = $container->getDefinition(TableOfContentProcessor::class));
            self::assertSame('toc', $def->getArgument('$tableOfContentProperty'));
            self::assertSame(1, $def->getArgument('$minDepth'));
            self::assertSame(3, $def->getArgument('$maxDepth'));
        }];

        yield 'last_modified disabled' => ['last_modified_disabled', function (ContainerBuilder $container): void {
            self::assertFalse($container->hasDefinition(LastModifiedProcessor::class));
        }];

        yield 'last_modified config' => ['last_modified_config', function (ContainerBuilder $container): void {
            self::assertNotNull($def = $container->getDefinition(LastModifiedProcessor::class));
            self::assertSame('updatedAt', $def->getArgument('$property'));
            self::assertSame('/usr/bin/git', $def->getArgument('$gitLastModified')->getArgument('$gitPath'));
        }];
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
