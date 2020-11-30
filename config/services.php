<?php

/*
 * This file is part of the "StenopePHP/Stenope" bundle.
 *
 * @author Thomas Jarrand <thomas.jarrand@gmail.com>
 */

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use Psr\Log\LoggerInterface;
use Stenope\Bundle\Builder;
use Stenope\Bundle\Builder\PageList;
use Stenope\Bundle\Builder\Sitemap;
use Stenope\Bundle\Command\BuildCommand;
use Stenope\Bundle\ContentManager;
use Stenope\Bundle\Decoder\HtmlDecoder;
use Stenope\Bundle\Decoder\MarkdownDecoder;
use Stenope\Bundle\DependencyInjection\tags;
use Stenope\Bundle\EventListener\Informator;
use Stenope\Bundle\EventListener\SitemapListener;
use Stenope\Bundle\Highlighter\Prism;
use Stenope\Bundle\Highlighter\Pygments;
use Stenope\Bundle\HttpKernel\Controller\ArgumentResolver\ContentArgumentResolver;
use Stenope\Bundle\Processor\CodeHighlightProcessor;
use Stenope\Bundle\Processor\ExtractTitleFromHtmlContentProcessor;
use Stenope\Bundle\Processor\HtmlAnchorProcessor;
use Stenope\Bundle\Processor\HtmlExternalLinksProcessor;
use Stenope\Bundle\Processor\HtmlIdProcessor;
use Stenope\Bundle\Processor\HtmlImageProcessor;
use Stenope\Bundle\Processor\LastModifiedProcessor;
use Stenope\Bundle\Processor\LocalLinksProcessor;
use Stenope\Bundle\Processor\SlugProcessor;
use Stenope\Bundle\Provider\Factory\ContentProviderFactory;
use Stenope\Bundle\Provider\Factory\LocalFilesystemProviderFactory;
use Stenope\Bundle\Routing\ContentUrlGenerator;
use Stenope\Bundle\Routing\UrlGenerator;
use Stenope\Bundle\Serializer\Normalizer\SkippingInstantiatedObjectDenormalizer;
use Stenope\Bundle\Service\ImageAssetUtils;
use Stenope\Bundle\Service\Parsedown;
use Stenope\Bundle\Twig\ContentExtension;
use Stenope\Bundle\Twig\ContentRuntime;
use Symfony\Component\Asset\Packages;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Stopwatch\Stopwatch;
use Symfony\Component\String\Slugger\SluggerInterface;

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
        ->set(ContentUrlGenerator::class)->args(['$router' => service('router')])

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
        ->set(HtmlIdProcessor::class)->args([
            '$property' => 'content',
            '$slugger' => service(SluggerInterface::class),
        ])
        ->set(HtmlAnchorProcessor::class)
        ->set(HtmlExternalLinksProcessor::class)
        ->set(ExtractTitleFromHtmlContentProcessor::class)
        ->set(HtmlImageProcessor::class)->args(['$imageAssetUtils' => service(ImageAssetUtils::class)])
        ->set(CodeHighlightProcessor::class)->args(['$highlighter' => service(Prism::class)])
        ->set(LocalLinksProcessor::class)->args(['$urlGenerator' => service(ContentUrlGenerator::class)])
    ;
};
