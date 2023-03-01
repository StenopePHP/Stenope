<?php

/*
 * This file is part of the "StenopePHP/Stenope" bundle.
 *
 * @author Thomas Jarrand <thomas.jarrand@gmail.com>
 */

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use Psr\Log\LoggerInterface;
use Stenope\Bundle\Behaviour\HtmlCrawlerManagerInterface;
use Stenope\Bundle\DependencyInjection\tags;
use Stenope\Bundle\Highlighter\Prism;
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
use Stenope\Bundle\Routing\ContentUrlResolver;
use Stenope\Bundle\Service\AssetUtils;
use Stenope\Bundle\Service\Git\LastModifiedFetcher;
use Stenope\Bundle\TableOfContent\CrawlerTableOfContentGenerator;
use Symfony\Component\String\Slugger\SluggerInterface;

require_once __DIR__ . '/tags.php';

return static function (ContainerConfigurator $container): void {
    $container->services()->defaults()->tag(tags\content_processor)
        ->set(LastModifiedProcessor::class)->args([
            '$property' => abstract_arg('lastModified property'),
            '$gitLastModified' => inline_service(LastModifiedFetcher::class)->args([
                '$gitPath' => abstract_arg('git path'),
                '$logger' => service(LoggerInterface::class)->nullOnInvalid(),
            ]),
        ])
        ->set(SlugProcessor::class)->args([
            '$property' => abstract_arg('slug property'),
        ])
        ->set(HtmlIdProcessor::class)
            ->args([
                '$slugger' => service(SluggerInterface::class),
                '$crawlers' => service(HtmlCrawlerManagerInterface::class),
                '$property' => abstract_arg('content property'),
            ])
        ->set(HtmlAnchorProcessor::class)->args([
            '$crawlers' => service(HtmlCrawlerManagerInterface::class),
            '$property' => abstract_arg('content property'),
            '$selector' => abstract_arg('HTML elements selector'),
        ])
        ->set(HtmlExternalLinksProcessor::class)->args([
            '$crawlers' => service(HtmlCrawlerManagerInterface::class),
            '$property' => abstract_arg('content property'),
        ])
        ->set(ExtractTitleFromHtmlContentProcessor::class)->args([
            '$crawlers' => service(HtmlCrawlerManagerInterface::class),
            '$titleProperty' => abstract_arg('title property'),
            '$contentProperty' => abstract_arg('content property'),
        ])
        ->set(CodeHighlightProcessor::class)->args([
            '$crawlers' => service(HtmlCrawlerManagerInterface::class),
            '$highlighter' => service(Prism::class),
            '$property' => abstract_arg('content property'),
        ])
        ->set(ResolveContentLinksProcessor::class)->args([
            '$resolver' => service(ContentUrlResolver::class),
            '$crawlers' => service(HtmlCrawlerManagerInterface::class),
            '$property' => abstract_arg('content property'),
        ])
        ->set(AssetsProcessor::class)->args([
            '$assetUtils' => service(AssetUtils::class),
            '$crawlers' => service(HtmlCrawlerManagerInterface::class),
            '$property' => abstract_arg('content property'),
        ])
        ->set(TableOfContentProcessor::class)
            ->args([
                '$generator' => service(CrawlerTableOfContentGenerator::class),
                '$tableOfContentProperty' => abstract_arg('table of content property'),
                '$contentProperty' => abstract_arg('content property'),
                '$minDepth' => abstract_arg('min depth'),
                '$maxDepth' => abstract_arg('max depth'),
                '$crawlers' => service(HtmlCrawlerManagerInterface::class),
            ])
            ->tag(tags\content_processor, ['priority' => -100])
    ;
};
