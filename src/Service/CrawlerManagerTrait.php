<?php

/*
 * This file is part of the "StenopePHP/Stenope" bundle.
 *
 * @author Thomas Jarrand <thomas.jarrand@gmail.com>
 */

namespace Stenope\Bundle\Service;

use Symfony\Component\DomCrawler\Crawler;

/**
 * @internal
 */
trait CrawlerManagerTrait
{
    /**
     * Extracts the raw HTML content from the body tag if available,
     * avoiding to return the encapsulating head, meta and body tags,
     * which are useless to render HTML content as part of an existing page.
     */
    private function getRawHtmlContent(Crawler $crawler): string
    {
        if (1 === ($body = $crawler->filter('body'))->count()) {
            return trim($body->html());
        }

        return trim($crawler->html());
    }

    private function createCrawler(string $html): ?Crawler
    {
        $html = trim($html);
        $crawler = new Crawler($html);

        try {
            // Pre-check the content is valid HTML
            $crawler->html();
        } catch (\Exception $e) {
            // Content is not valid HTML.
            return null;
        }

        if (0 === $crawler->filter('meta[charset]')->count()) {
            // https://github.com/symfony/symfony/pull/46212
            // Let's assume the parsed contents will always be HTML5 with UTF-8 charset
            // and explicitly add the doctype and charset meta tag,
            // so that it can be properly parsed by Symfony's Crawler:
            $crawler = new Crawler(<<<HTML
                <!DOCTYPE html>
                <html>
                    <head>
                        <meta charset="UTF-8" />
                    </head>
                    <body>
                        $html
                    </body>
                </html>
                HTML
            );
        }

        return $crawler;
    }
}
