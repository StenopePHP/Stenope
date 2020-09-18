<?php

/*
 * This file is part of the "Tom32i/Content" bundle.
 *
 * @author Thomas Jarrand <thomas.jarrand@gmail.com>
 */

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use Content\Builder;
use Content\Builder\PageList;
use Content\Builder\Sitemap;
use Content\Command\BuildCommand;
use Content\ContentManager;
use Content\Decoder\HtmlDecoder;
use Content\Decoder\MarkdownDecoder;
use Content\DependencyInjection\tags;
use Content\EventListener\Informator;
use Content\EventListener\SitemapListener;
use Content\Highlighter\Prism;
use Content\Highlighter\Pygments;
use Content\HttpKernel\Controller\ArgumentResolver\ContentArgumentResolver;
use Content\Processor\CodeHighlightProcessor;
use Content\Processor\HtmlAnchorProcessor;
use Content\Processor\HtmlExternalLinksProcessor;
use Content\Processor\HtmlIdProcessor;
use Content\Processor\LastModifiedProcessor;
use Content\Processor\SlugProcessor;
use Content\Provider\Factory\ContentProviderFactory;
use Content\Provider\Factory\LocalFilesystemProviderFactory;
use Content\Routing\UrlGenerator;
use Content\Serializer\Normalizer\SkippingInstantiatedObjectDenormalizer;
use Content\Service\Parsedown;
use Content\Twig\ContentExtension;
use Content\Twig\ContentRuntime;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Stopwatch\Stopwatch;

require_once __DIR__ . '/tags.php';

return static function (ContainerConfigurator $container): void {
    $container->services()
        // Content manager
        ->set(ContentManager::class)->args([
            '$decoder' => service('serializer'),
            '$denormalizer' => service('serializer'),
            '$propertyAccessor' => service('property_accessor'),
            '$contentProviders' => tagged_iterator(tags\content_provider),
            '$processors' => tagged_iterator(tags\content_processor),
            '$stopwatch' => service('debug.stopwatch')->nullOnInvalid(),
        ])

        // Content providers factories
        ->set(ContentProviderFactory::class)->args(['$factories' => tagged_iterator(tags\content_provider_factory)])
        ->set(LocalFilesystemProviderFactory::class)->tag(tags\content_provider_factory)

        // Build
        ->set(BuildCommand::class)->args([
            '$builder' => service(Builder::class),
            '$stopwatch' => service('content.build.stopwatch'),
        ])
        ->tag('console.command', ['command' => BuildCommand::getDefaultName()])

        ->set(Builder::class)->args([
            '$router' => service('router'),
            '$httpKernel' => service('kernel'),
            '$templating' => service('twig'),
            '$pageList' => service(PageList::class),
            '$sitemap' => service(Sitemap::class),
            '$buildDir' => 'The build dir, defined by the extension',
            '$filesToCopy' => 'The files to copy after build, defined by the extension',
            '$logger' => service(LoggerInterface::class)->nullOnInvalid(),
            '$stopwatch' => service('content.build.stopwatch'),
        ])

        ->set('content.build.stopwatch', Stopwatch::class)->args([true])

        // Sitemap
        ->set(PageList::class)
        ->set(Sitemap::class)
        ->set(SitemapListener::class)
            ->args([
                '$router' => service('router'),
                '$sitemap' => service(Sitemap::class),
            ])
            ->tag('kernel.event_subscriber')

        ->set(Informator::class)
            ->args([
                '$urlGenerator' => service(UrlGeneratorInterface::class),
                '$twig' => service('twig'),
            ])
            ->tag('kernel.event_subscriber')

        // Markdown and code highlighting
        ->set(Parsedown::class)
        ->set(Pygments::class)
        ->set(Prism::class)->args([
            '$executable' => null,
            '$stopwatch' => service('debug.stopwatch')->nullOnInvalid(),
            '$logger' => service(LoggerInterface::class)->nullOnInvalid(),
        ])->tag('kernel.event_listener', ['event' => KernelEvents::TERMINATE, 'method' => 'stop'])

        // Serializer
        ->set(SkippingInstantiatedObjectDenormalizer::class)->tag('serializer.normalizer')

        // Decoders
        ->set(MarkdownDecoder::class)
            ->args(['$parser' => service(Parsedown::class)])
            ->tag('serializer.encoder')
        ->set(HtmlDecoder::class)->tag('serializer.encoder')

        // Url generator decorator
        ->set(UrlGenerator::class)
            ->decorate(UrlGeneratorInterface::class)
            ->args([
                '$urlGenerator' => service(UrlGenerator::class . '.inner'),
                '$pageList' => service(PageList::class),
            ])

        // Symfony HttpKernel controller argument resolver
        ->set(ContentArgumentResolver::class)
            ->args(['$contentManager' => service(ContentManager::class)])
            ->tag('controller.argument_value_resolver', [
                'priority' => 110, // Prior to RequestAttributeValueResolver to resolve from route attribute
            ])

        // Twig
        ->set(ContentExtension::class)->tag('twig.extension')
        ->set(ContentRuntime::class)
            ->args(['$contentManager' => service(ContentManager::class)])
            ->tag('twig.runtime')
    ;

    // Tagged Property handlers:
    $container->services()->defaults()->tag(tags\content_processor)
        ->set(LastModifiedProcessor::class)
        ->set(SlugProcessor::class)
        ->set(HtmlIdProcessor::class)
        ->set(HtmlAnchorProcessor::class)
        ->set(HtmlExternalLinksProcessor::class)
        ->set(CodeHighlightProcessor::class)->args(['$highlighter' => service(Prism::class)])
    ;
};
