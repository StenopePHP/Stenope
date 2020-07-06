<?php

/*
 * This file is part of the "Tom32i/Content" bundle.
 *
 * @author Thomas Jarrand <thomas.jarrand@gmail.com>
 */

namespace Content\Asset;

use Content\Builder\AssetList;
use Symfony\Component\Asset\PackageInterface;

class Packages implements PackageInterface
{
    private $package;
    private $assetList;

    public function __construct(PackageInterface $package, AssetList $assetList)
    {
        $this->package = $package;
        $this->assetList = $assetList;
    }

    public function getVersion($path)
    {
        $this->assetList[] = $path;

        return $this->package->getVersion($path);
    }

    public function getUrl($path)
    {
        $this->assetList[] = $path;

        return $this->package->getUrl($path);
    }
}
