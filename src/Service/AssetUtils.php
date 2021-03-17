<?php

/*
 * This file is part of the "StenopePHP/Stenope" bundle.
 *
 * @author Thomas Jarrand <thomas.jarrand@gmail.com>
 */

namespace Stenope\Bundle\Service;

use Symfony\Component\Asset\Packages;

class AssetUtils
{
    private Packages $assets;

    public function __construct(Packages $assets)
    {
        $this->assets = $assets;
    }

    public function getUrl(string $url): string
    {
        if (preg_match('#^((\w+:)?//)?(.+)$#', $url, $matches)) {
            // Only process local assets (ignoring urls starting by a scheme or "//").
            if (!$matches[1]) {
                return $this->assets->getUrl($matches[3]);
            }
        }

        return $url;
    }
}
