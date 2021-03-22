<?php

/*
 * This file is part of the "StenopePHP/Stenope" bundle.
 *
 * @author Thomas Jarrand <thomas.jarrand@gmail.com>
 */

namespace Stenope\Bundle\Processor;

use Stenope\Bundle\Behaviour\ProcessorInterface;
use Stenope\Bundle\Content;
use Stenope\Bundle\Service\AssetUtils;
use Symfony\Component\DomCrawler\Crawler;

/**
 * Attempt to resolve local assets URLs using the Asset component for images and links.
 */
class AssetsProcessor implements ProcessorInterface
{
    private AssetUtils $assetUtils;
    private string $property;

    public function __construct(AssetUtils $assetUtils, string $property = 'content')
    {
        $this->assetUtils = $assetUtils;
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

        foreach ($crawler->filter('img') as $element) {
            $element->setAttribute('src', $this->assetUtils->getUrl($element->getAttribute('src')));
        }

        foreach ($crawler->filter('a') as $element) {
            $element->setAttribute('href', $this->assetUtils->getUrl($element->getAttribute('href')));
        }

        $data[$this->property] = $crawler->html();
    }
}
