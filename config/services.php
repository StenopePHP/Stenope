<?php

/*
 * This file is part of the "StenopePHP/Stenope" bundle.
 *
 * @author Thomas Jarrand <thomas.jarrand@gmail.com>
 */

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use Psr\Log\LoggerInterface;
use Stenope\Bundle\Behaviour\HtmlCrawlerManagerInterface;
use Stenope\Bundle\Builder;
use Stenope\Bundle\Builder\PageList;
use Stenope\Bundle\Builder\Sitemap;
use Stenope\Bundle\Command\BuildCommand;
use Stenope\Bundle\Command\DebugCommand;
use Stenope\Bundle\ContentManager;
use Stenope\Bundle\ContentManagerInterface;
use Stenope\Bundle\Decoder\HtmlDecoder;
use Stenope\Bundle\Decoder\MarkdownDecoder;
use Stenope\Bundle\DependencyInjection\tags;
use Stenope\Bundle\EventListener\Informator;
use Stenope\Bundle\EventListener\SitemapListener;
use Stenope\Bundle\ExpressionLanguage\ExpressionLanguage;
use Stenope\Bundle\Highlighter\Prism;
use Stenope\Bundle\Highlighter\Pygments;
use Stenope\Bundle\HttpKernel\Controller\ArgumentResolver\ContentArgumentResolver;
use Stenope\Bundle\Provider\Factory\ContentProviderFactory;
use Stenope\Bundle\Provider\Factory\LocalFilesystemProviderFactory;
use Stenope\Bundle\Routing\ContentUrlResolver;
use Stenope\Bundle\Routing\RouteInfoCollection;
use Stenope\Bundle\Routing\UrlGenerator;
use Stenope\Bundle\Serializer\Normalizer\SkippingInstantiatedObjectDenormalizer;
use Stenope\Bundle\Service\AssetUtils;
use Stenope\Bundle\Service\NaiveHtmlCrawlerManager;
use Stenope\Bundle\Service\Parsedown;
use Stenope\Bundle\Service\SharedHtmlCrawlerManager;
use Stenope\Bundle\TableOfContent\CrawlerTableOfContentGenerator;
use Stenope\Bundle\Twig\ContentExtension;
use Stenope\Bundle\Twig\ContentRuntime;
use Symfony\Component\Asset\Packages;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Mime\MimeTypesInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Stopwatch\Stopwatch;

require_once __DIR__ . '/tags.php';

return static function (ContainerConfigurator $container): void {
    $container->services()
        // Content manager
        ->set(ContentManager::class)->args([
            '$decoder' => service('serializer'),
            '$denormalizer' => service('serializer'),
            '$crawlers' => service(HtmlCrawlerManagerInterface::class),
            '$contentProviders' => tagged_iterator(tags\content_provider),
            '$processors' => tagged_iterator(tags\content_processor),
            '$propertyAccessor' => service('property_accessor'),
            '$expressionLanguage' => service(ExpressionLanguage::class)->nullOnInvalid(),
            '$stopwatch' => service('debug.stopwatch')->nullOnInvalid(),
        ])->call('setContentManager', [service(ContentManagerInterface::class)])
        ->alias(ContentManagerInterface::class, ContentManager::class)

        // Content providers factories
        ->set(ContentProviderFactory::class)->args(['$factories' => tagged_iterator(tags\content_provider_factory)])
        ->set(LocalFilesystemProviderFactory::class)->tag(tags\content_provider_factory)

        // Debug
        ->set(DebugCommand::class)->args([
            '$manager' => service(ContentManagerInterface::class),
            '$stopwatch' => service('stenope.build.stopwatch'),
            '$registeredTypes' => 'The known content types, defined by the extension',
        ])
        ->tag('console.command', ['command' => 'debug:stenope:content'])

        // Expression Language
        ->set(ExpressionLanguage::class)->args([
            '$providers' => tagged_iterator(tags\expression_language_provider),
        ])

        // Build
        ->set(BuildCommand::class)->args([
            '$builder' => service(Builder::class),
            '$stopwatch' => service('stenope.build.stopwatch'),
        ])
        ->tag('console.command', ['command' => 'stenope:build'])

        ->set(Builder::class)->args([
            '$router' => service('router'),
            '$routesInfo' => service(RouteInfoCollection::class),
            '$httpKernel' => service('kernel'),
            '$templating' => service('twig'),
            '$mimeTypes' => service(MimeTypesInterface::class),
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
                '$routesInfo' => service(RouteInfoCollection::class),
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
                '$routesInfo' => service(RouteInfoCollection::class),
                '$urlGenerator' => service(UrlGenerator::class . '.inner'),
                '$pageList' => service(PageList::class),
            ])
        ->set(RouteInfoCollection::class)->args([
            '$router' => service('router'),
        ])
        ->set(ContentUrlResolver::class)->args([
            '$router' => service('router'),
            '$routes' => 'The routes to resolve types, defined by the extension',
        ])

        // Symfony HttpKernel controller argument resolver
        ->set(ContentArgumentResolver::class)
            ->args(['$contentManager' => service(ContentManagerInterface::class)])
            ->tag('controller.argument_value_resolver', [
                'priority' => 110, // Prior to RequestAttributeValueResolver to resolve from route attribute
            ])

        // Twig
        ->set(ContentExtension::class)->tag('twig.extension')
        ->set(ContentRuntime::class)
            ->args(['$contentManager' => service(ContentManagerInterface::class)])
            ->tag('twig.runtime')

        // Assets
        ->set(AssetUtils::class)
            ->args(['$assets' => service(Packages::class)])

        // Table of content
        ->set(CrawlerTableOfContentGenerator::class)

        // HTML Crawler Manager
        ->set(NaiveHtmlCrawlerManager::class)
        ->set(SharedHtmlCrawlerManager::class)
        ->alias(HtmlCrawlerManagerInterface::class, NaiveHtmlCrawlerManager::class);
};
