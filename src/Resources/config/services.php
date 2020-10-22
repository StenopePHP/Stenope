<?php

/*
 * This file is part of the "StenopePHP/Stenope" bundle.
 *
 * @author Thomas Jarrand <thomas.jarrand@gmail.com>
 */

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use Psr\Log\LoggerInterface;
use Stenope\Builder;
use Stenope\Builder\PageList;
use Stenope\Builder\Sitemap;
use Stenope\Command\BuildCommand;
use Stenope\ContentManager;
use Stenope\Decoder\HtmlDecoder;
use Stenope\Decoder\MarkdownDecoder;
use Stenope\DependencyInjection\tags;
use Stenope\EventListener\Informator;
use Stenope\EventListener\SitemapListener;
use Stenope\Highlighter\Prism;
use Stenope\Highlighter\Pygments;
use Stenope\HttpKernel\Controller\ArgumentResolver\ContentArgumentResolver;
use Stenope\Processor\CodeHighlightProcessor;
use Stenope\Processor\ExtractTitleFromHtmlContentProcessor;
use Stenope\Processor\HtmlAnchorProcessor;
use Stenope\Processor\HtmlExternalLinksProcessor;
use Stenope\Processor\HtmlIdProcessor;
use Stenope\Processor\HtmlImageProcessor;
use Stenope\Processor\LastModifiedProcessor;
use Stenope\Processor\SlugProcessor;
use Stenope\Provider\Factory\ContentProviderFactory;
use Stenope\Provider\Factory\LocalFilesystemProviderFactory;
use Stenope\Routing\UrlGenerator;
use Stenope\Serializer\Normalizer\SkippingInstantiatedObjectDenormalizer;
use Stenope\Service\ImageAssetUtils;
use Stenope\Service\Parsedown;
use Stenope\Twig\ContentExtension;
use Stenope\Twig\ContentRuntime;
use Symfony\Component\Asset\Packages;
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
            '$stopwatch' => service('stenope.build.stopwatch'),
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
            '$stopwatch' => service('stenope.build.stopwatch'),
        ])

        ->set('stenope.build.stopwatch', Stopwatch::class)->args([true])

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

        // Assets
        ->set(ImageAssetUtils::class)
            ->args(['$assets' => service(Packages::class)])
    ;

    // Tagged Property handlers:
    $container->services()->defaults()->tag(tags\content_processor)
        ->set(LastModifiedProcessor::class)
        ->set(SlugProcessor::class)
        ->set(HtmlIdProcessor::class)
        ->set(HtmlAnchorProcessor::class)
        ->set(HtmlExternalLinksProcessor::class)
        ->set(ExtractTitleFromHtmlContentProcessor::class)
        ->set(HtmlImageProcessor::class)->args(['$imageAssetUtils' => service(ImageAssetUtils::class)])
        ->set(CodeHighlightProcessor::class)->args(['$highlighter' => service(Prism::class)])
    ;
};
