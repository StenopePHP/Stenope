<?php

/*
 * This file is part of the "StenopePHP/Stenope" bundle.
 *
 * @author Thomas Jarrand <thomas.jarrand@gmail.com>
 */

namespace Stenope\Bundle\DependencyInjection;

use Stenope\Bundle\Behaviour\HtmlCrawlerManagerInterface;
use Stenope\Bundle\Behaviour\ProcessorInterface;
use Stenope\Bundle\Builder;
use Stenope\Bundle\Command\DebugCommand;
use Stenope\Bundle\ExpressionLanguage\ExpressionLanguage as StenopeExpressionLanguage;
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
use Stenope\Bundle\Provider\ContentProviderInterface;
use Stenope\Bundle\Provider\Factory\ContentProviderFactory;
use Stenope\Bundle\Provider\Factory\ContentProviderFactoryInterface;
use Stenope\Bundle\Routing\ContentUrlResolver;
use Stenope\Bundle\Routing\ResolveContentRoute;
use Stenope\Bundle\Service\SharedHtmlCrawlerManager;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader\PhpFileLoader;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage;

/**
 * @final
 */
class StenopeExtension extends Extension
{
    public function load(array $configs, ContainerBuilder $container): void
    {
        $loader = new PhpFileLoader($container, new FileLocator(__DIR__ . '/../../config'));
        $loader->load('services.php');

        $container->registerForAutoconfiguration(ContentProviderFactoryInterface::class)->addTag('stenope.content_provider_factory');
        $container->registerForAutoconfiguration(ContentProviderInterface::class)->addTag('stenope.content_provider');
        $container->registerForAutoconfiguration(ProcessorInterface::class)->addTag('stenope.processor');

        $config = $this->processConfiguration($this->getConfiguration($configs, $container), $configs);

        $container->getDefinition(Builder::class)->replaceArgument('$buildDir', $config['build_dir']);
        $container->getDefinition(Builder::class)->replaceArgument('$filesToCopy', $config['copy']);

        if ($config['shared_html_crawlers']) {
            $container->setAlias(HtmlCrawlerManagerInterface::class, SharedHtmlCrawlerManager::class);
        }

        $this->processProcessors($loader, $container, $config['processors']);
        $this->processProviders($container, $config['providers']);
        $this->processLinkResolvers($container, $config['resolve_links']);

        $registeredTypes = array_keys($config['providers']);
        sort($registeredTypes, SORT_NATURAL);
        $container->getDefinition(DebugCommand::class)
            ->replaceArgument('$registeredTypes', $registeredTypes)
        ;

        if (!class_exists(ExpressionLanguage::class)) {
            $container->removeDefinition(StenopeExpressionLanguage::class);
        }
    }

    public function getNamespace(): string
    {
        return 'http://stenope.com/schema/dic/stenope';
    }

    public function getXsdValidationBasePath(): string
    {
        return __DIR__ . '/../../config/schema';
    }

    private function processProviders(ContainerBuilder $container, array $providersConfig): void
    {
        foreach ($providersConfig as $class => ['type' => $type, 'config' => $config]) {
            $container->register("stenope.provider.$type.$class", ContentProviderInterface::class)
                ->setFactory([new Reference(ContentProviderFactory::class), 'create'])
                ->setArgument('$type', $type)
                ->setArgument('$config', ['class' => $class] + $config)
                ->addTag('stenope.content_provider')
            ;
        }
    }

    private function processLinkResolvers(ContainerBuilder $container, array $links): void
    {
        $references = [];
        foreach ($links as $class => $link) {
            $id = sprintf(".%s.$class", ResolveContentRoute::class);

            $container->register($id, ResolveContentRoute::class)->setArguments([
                $link['route'],
                $link['slug'],
                $link['defaults'] ?? [],
            ]);

            $references[$class] = new Reference($id);
        }

        $container->getDefinition(ContentUrlResolver::class)->replaceArgument('$routes', $references);
    }

    private function processProcessors(PhpFileLoader $loader, ContainerBuilder $container, array $processorsConfig): void
    {
        if (false === $processorsConfig['enabled']) {
            return;
        }

        $loader->load('processors.php');

        $contentProperty = $processorsConfig['content_property'];

        if ($this->isProcessorEnabled($processorsConfig['slug'], SlugProcessor::class, $container)) {
            $container->getDefinition(SlugProcessor::class)
                ->setArgument('$property', $processorsConfig['slug']['property'])
            ;
        }

        if ($this->isProcessorEnabled($processorsConfig['assets'], AssetsProcessor::class, $container)) {
            $container->getDefinition(AssetsProcessor::class)
                ->setArgument('$property', $contentProperty)
            ;
        }

        if ($this->isProcessorEnabled($processorsConfig['resolve_content_links'], ResolveContentLinksProcessor::class, $container)) {
            $container->getDefinition(ResolveContentLinksProcessor::class)
                ->setArgument('$property', $contentProperty)
            ;
        }

        if ($this->isProcessorEnabled($processorsConfig['external_links'], HtmlExternalLinksProcessor::class, $container)) {
            $container->getDefinition(HtmlExternalLinksProcessor::class)
                ->setArgument('$property', $contentProperty)
            ;
        }

        if ($this->isProcessorEnabled($processorsConfig['anchors'], HtmlAnchorProcessor::class, $container)) {
            $container->getDefinition(HtmlAnchorProcessor::class)
                ->setArgument('$selector', $processorsConfig['anchors']['selector'])
                ->setArgument('$property', $contentProperty)
            ;
        }

        if ($this->isProcessorEnabled($processorsConfig['html_title'], ExtractTitleFromHtmlContentProcessor::class, $container)) {
            $container->getDefinition(ExtractTitleFromHtmlContentProcessor::class)
                ->setArgument('$titleProperty', $processorsConfig['html_title']['property'])
                ->setArgument('$contentProperty', $contentProperty)
            ;
        }

        if ($this->isProcessorEnabled($processorsConfig['html_elements_ids'], HtmlIdProcessor::class, $container)) {
            $container->getDefinition(HtmlIdProcessor::class)
                ->setArgument('$property', $contentProperty)
            ;
        }

        if ($this->isProcessorEnabled($processorsConfig['code_highlight'], CodeHighlightProcessor::class, $container)) {
            $container->getDefinition(CodeHighlightProcessor::class)
                ->setArgument('$property', $contentProperty)
            ;
        }

        if ($this->isProcessorEnabled($processorsConfig['toc'], TableOfContentProcessor::class, $container)) {
            $container->getDefinition(TableOfContentProcessor::class)
                ->setArgument('$tableOfContentProperty', $processorsConfig['toc']['property'])
                ->setArgument('$minDepth', $processorsConfig['toc']['min_depth'])
                ->setArgument('$maxDepth', $processorsConfig['toc']['max_depth'])
                ->setArgument('$contentProperty', $contentProperty)
            ;
        }

        if ($this->isProcessorEnabled($processorsConfig['last_modified'], LastModifiedProcessor::class, $container)) {
            $container->getDefinition(LastModifiedProcessor::class)
                ->setArgument('$property', $processorsConfig['last_modified']['property'])
            ;

            if ($processorsConfig['last_modified']['git']['enabled']) {
                // Configure the git fetcher inlined service definition:
                /** @var Definition $fetcherDef */
                $fetcherDef = $container->getDefinition(LastModifiedProcessor::class)->getArgument('$gitLastModified');
                $fetcherDef->setArgument('$gitPath', $processorsConfig['last_modified']['git']['path']);
            } else {
                // Remove the git fetcher inlined service definition if disabled:
                $container->getDefinition(LastModifiedProcessor::class)->setArgument('$gitLastModified', null);
            }
        }
    }

    private function isProcessorEnabled(array $config, string $processorId, ContainerBuilder $container): bool
    {
        if (!$enabled = $config['enabled']) {
            $container->removeDefinition($processorId);
        }

        return $enabled;
    }
}
