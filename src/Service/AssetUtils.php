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
        if (null === parse_url($url, PHP_URL_SCHEME) && !str_starts_with($url, '//') && !str_starts_with($url, '#')) {
            // Only process local assets (ignoring urls starting by a scheme or "//").
            return $this->assets->getUrl($url);
        }

        return $url;
    }
}
