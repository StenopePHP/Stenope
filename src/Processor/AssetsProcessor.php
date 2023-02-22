<?php

/*
 * This file is part of the "StenopePHP/Stenope" bundle.
 *
 * @author Thomas Jarrand <thomas.jarrand@gmail.com>
 */

namespace Stenope\Bundle\Processor;

use Stenope\Bundle\Behaviour\HtmlCrawlerManagerInterface;
use Stenope\Bundle\Behaviour\ProcessorInterface;
use Stenope\Bundle\Content;
use Stenope\Bundle\Service\AssetUtils;

/**
 * Attempt to resolve local assets URLs using the Asset component for images and links.
 */
class AssetsProcessor implements ProcessorInterface
{
    private AssetUtils $assetUtils;
    private HtmlCrawlerManagerInterface $crawlers;
    private string $property;

    public function __construct(
        AssetUtils $assetUtils,
        HtmlCrawlerManagerInterface $crawlers,
        string $property = 'content'
    ) {
        $this->assetUtils = $assetUtils;
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

        foreach ($crawler->filter('img') as $element) {
            $element->setAttribute('src', $this->assetUtils->getUrl($element->getAttribute('src')));
        }

        foreach ($crawler->filter('a') as $element) {
            $element->setAttribute('href', $this->assetUtils->getUrl($element->getAttribute('href')));
        }

        $this->crawlers->save($content, $data, $this->property);
    }
}
