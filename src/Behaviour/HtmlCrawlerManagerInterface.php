<?php

/*
 * This file is part of the "StenopePHP/Stenope" bundle.
 *
 * @author Thomas Jarrand <thomas.jarrand@gmail.com>
 */

namespace Stenope\Bundle\Behaviour;

use Symfony\Component\DomCrawler\Crawler;

interface HtmlCrawlerManagerInterface
{
    /**
     * Get HTML Crawler for the given property (creats it if needed)
     */
    public function get(array &$data, string $property): ?Crawler;

    /**
     * Dump the current state of the HTML Crawler into data for the given property.
     */
    public function save(array &$data, string $property, bool $force = false): void;

    /**
     * Dump the current state of all HTML Crawlers into data for their respective property.
     */
    public function saveAll(array &$data): void;
}