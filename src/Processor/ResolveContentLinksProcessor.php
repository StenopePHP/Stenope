<?php

/*
 * This file is part of the "StenopePHP/Stenope" bundle.
 *
 * @author Thomas Jarrand <thomas.jarrand@gmail.com>
 */

namespace Stenope\Bundle\Processor;

use Stenope\Bundle\Behaviour\ContentManagerAwareInterface;
use Stenope\Bundle\Behaviour\ContentManagerAwareTrait;
use Stenope\Bundle\Behaviour\HtmlCrawlerManagerInterface;
use Stenope\Bundle\Behaviour\ProcessorInterface;
use Stenope\Bundle\Content;
use Stenope\Bundle\ReverseContent\RelativeLinkContext;
use Stenope\Bundle\Routing\ContentUrlResolver;

/**
 * Resolve relative links between contents using the route declared in config.
 */
class ResolveContentLinksProcessor implements ProcessorInterface, ContentManagerAwareInterface
{
    use ContentManagerAwareTrait;

    private ContentUrlResolver $resolver;
    private HtmlCrawlerManagerInterface $crawlers;
    private string $property;

    public function __construct(
        ContentUrlResolver $resolver,
        HtmlCrawlerManagerInterface $crawlers,
        string $property = 'content'
    ) {
        $this->resolver = $resolver;
        $this->crawlers = $crawlers;
        $this->property = $property;
    }

    public function __invoke(array &$data, Content $content): void
    {
        if (!isset($data[$this->property])) {
            return;
        }

        $crawler = $this->crawlers->get($content, $data, $this->property);

        if (!$crawler) {
            return;
        }

        foreach ($crawler->filter('a') as $link) {
            $this->processLink($link, $content);
        }

        $this->crawlers->save($content, $data, $this->property);
    }

    private function processLink(\DOMElement $link, Content $currentContent): void
    {
        if (!$href = $link->getAttribute('href')) {
            return;
        }

        // External link / link with a scheme
        if (preg_match('@^(\w+:)?//@', $href)) {
            return;
        }

        // anchors
        if (str_starts_with($href, '#')) {
            return;
        }

        // Link to website root
        if (str_starts_with($href, '/')) {
            return;
        }

        // Internal content link
        $path = parse_url($href, PHP_URL_PATH);
        // Extract fragment (hash / anchor, if any)
        $fragment = parse_url($href, PHP_URL_FRAGMENT);

        $context = new RelativeLinkContext($currentContent->getMetadata(), $path);
        if ($content = $this->contentManager->reverseContent($context)) {
            $url = $this->resolver->resolveUrl($content);
            // redirect to proper content, with specified anchor:
            $link->setAttribute('href', $fragment ? "$url#$fragment" : $url);
        }
    }
}
