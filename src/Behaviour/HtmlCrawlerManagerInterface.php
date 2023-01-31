<?php

/*
 * This file is part of the "StenopePHP/Stenope" bundle.
 *
 * @author Thomas Jarrand <thomas.jarrand@gmail.com>
 */

namespace Stenope\Bundle\Behaviour;

use Stenope\Bundle\Content;
use Symfony\Component\DomCrawler\Crawler;

/**
 * Manager for getting an HTML crawler instance for HTML content properties,
 * meant to be rendered inside a page.
 */
interface HtmlCrawlerManagerInterface
{
    /**
     * Get HTML Crawler for the given property (creates it if needed)
     */
    public function get(Content $content, array $data, string $property): ?Crawler;

    /**
     * Dump the current state of the HTML Crawler into data for the given property.
     */
    public function save(Content $content, array &$data, string $property): void;

    /**
     * Dump the current state of all HTML Crawlers into data for their respective property.
     */
    public function saveAll(Content $content, array &$data): void;
}
