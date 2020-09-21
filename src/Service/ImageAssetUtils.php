<?php

/*
 * This file is part of the "Tom32i/Content" bundle.
 *
 * @author Thomas Jarrand <thomas.jarrand@gmail.com>
 */

namespace Content\Service;

use Symfony\Component\Asset\Packages;

class ImageAssetUtils
{
    private Packages $assets;

    public function __construct(Packages $assets)
    {
        $this->assets = $assets;
    }

    public function getUrl(string $url): string
    {
        if (preg_match('#((https?:)?//)?(.+)$#', $url, $matches)) {
            // Only process local images (ignoring urls starting by "http", "https" or "//").
            if (!$matches[1]) {
                return $this->assets->getUrl($matches[3]);
            }
        }

        return $url;
    }
}
