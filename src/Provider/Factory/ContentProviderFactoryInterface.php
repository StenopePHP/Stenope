<?php

/*
 * This file is part of the "Tom32i/Content" bundle.
 *
 * @author Thomas Jarrand <thomas.jarrand@gmail.com>
 */

namespace Content\Provider\Factory;

use Content\Provider\ContentProviderInterface;

/**
 * A factory to instantiate content providers based on type and config
 */
interface ContentProviderFactoryInterface
{
    public function create(string $type, array $config): ContentProviderInterface;

    public function supports(string $type, array $config): bool;
}
