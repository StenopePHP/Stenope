<?php

/*
 * This file is part of the "StenopePHP/Stenope" bundle.
 *
 * @author Thomas Jarrand <thomas.jarrand@gmail.com>
 */

namespace Stenope\Bundle\Processor;

use Stenope\Bundle\Behaviour\ContentManagerAwareInterface;
use Stenope\Bundle\Behaviour\ContentManagerAwareTrait;
use Stenope\Bundle\Behaviour\ProcessorInterface;
use Stenope\Bundle\Content;
use Stenope\Bundle\Routing\ContentUrlGenerator;
use Symfony\Component\DomCrawler\Crawler;

/**
 * Converts relative contents links to urls
 */
class LocalLinksProcessor implements ProcessorInterface, ContentManagerAwareInterface
{
    use ContentManagerAwareTrait;

    private ContentUrlGenerator $urlGenerator;
    private string $property;

    public function __construct(ContentUrlGenerator $urlGenerator, string $property = 'content')
    {
        $this->urlGenerator = $urlGenerator;
        $this->property = $property;
    }

    public function __invoke(array &$data, string $type, Content $content): void
    {
        if (!isset($data[$this->property])) {
            return;
        }

        $crawler = new Crawler($data[$this->property]);

        try {
            $crawler->html();
        } catch (\Exception $e) {
            // Content is not valid HTML.
            return;
        }

        $crawler = new Crawler($data[$this->property]);

        foreach ($crawler->filter('a') as $link) {
            $this->processLink($link, $content);
        }

        $data[$this->property] = $crawler->html();
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
        if ($resolved = $this->contentManager->reverseContent([
            'current_path' => $currentContent->getMetadata()['path'],
            'target_path' => $href,
        ])) {
            $url = $this->urlGenerator->generate($resolved);
            $link->setAttribute('href', $url);
        }
    }
}
