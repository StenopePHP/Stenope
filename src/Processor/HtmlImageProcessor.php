<?php

/*
 * This file is part of the "Tom32i/Content" bundle.
 *
 * @author Thomas Jarrand <thomas.jarrand@gmail.com>
 */

namespace Content\Processor;

use Content\Behaviour\ProcessorInterface;
use Content\Content;
use Content\Service\ImageAssetUtils;
use Symfony\Component\DomCrawler\Crawler;

class HtmlImageProcessor implements ProcessorInterface
{
    private ImageAssetUtils $imageAssetUtils;
    private string $property;

    public function __construct(ImageAssetUtils $imageAssetUtils, string $property = 'content')
    {
        $this->imageAssetUtils = $imageAssetUtils;
        $this->property = $property;
    }

    public function __invoke(array &$data, string $type, Content $content): void
    {
        if (!isset($data[$this->property]) || !$data[$this->property] instanceof Crawler) {
            return;
        }

        $crawler = $data[$this->property];

        foreach ($crawler->filter('img') as $element) {
            $element->setAttribute('src', $this->imageAssetUtils->getUrl($element->getAttribute('src')));
        }
    }
}
