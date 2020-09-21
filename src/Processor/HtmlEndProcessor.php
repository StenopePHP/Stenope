<?php

/*
 * This file is part of the "Tom32i/Content" bundle.
 *
 * @author Thomas Jarrand <thomas.jarrand@gmail.com>
 */

namespace Content\Processor;

use Content\Behaviour\ProcessorInterface;
use Content\Content;
use Symfony\Component\DomCrawler\Crawler;

class HtmlEndProcessor implements ProcessorInterface
{
    private string $property;

    public function __construct(string $property = 'content')
    {
        $this->property = $property;
    }

    public function __invoke(array &$data, string $type, Content $content): void
    {
        if (!isset($data[$this->property])) {
            return;
        }

        if ($data[$this->property] instanceof Crawler) {
            $data[$this->property] = $data[$this->property]->html();
        }
    }
}
