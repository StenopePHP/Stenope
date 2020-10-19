<?php

namespace App\Content\Processor;

use App\Model\Page;
use Content\Behaviour\ProcessorInterface;
use Content\Content;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * Converts local links to url
 */
class LocalLinkProcessor implements ProcessorInterface
{
    private UrlGeneratorInterface $router;
    private string $property;

    public function __construct(UrlGeneratorInterface $router, string $property = 'content')
    {
        $this->router = $router;
        $this->property = $property;
    }

    public function __invoke(array &$data, string $type, Content $content): void
    {
        $crawler = new Crawler($data[$this->property]);

        try {
            $crawler->html();
        } catch (\Exception $e) {
            // Content is not valid HTML.
            return;
        }

        $crawler = new Crawler($data[$this->property]);

        // Detect links starting with "./", susceptible to be pointing to a Github resource:
        foreach ($crawler->filter('a') as $link) {
            $this->processLink($link, $content);
        }

        $data[$this->property] = $crawler->html();
    }

    private function processLink(\DOMElement $link, Content $content): void
    {
        $href = $link->getAttribute('href');

        // External link
        if (preg_match('/^(https?:)?\/\//', $href)) {
            return;
        }

        // Internal page link
        if (preg_match('/(doc\/)?(.+)\.md/', $href, $matches)) {
            $url = $this->router->generate('page', ['name' => $matches[2]]);
            $link->setAttribute('href', $url);
        }

    }
}
