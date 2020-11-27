<?php

/*
 * This file is part of the "StenopePHP/Stenope" bundle.
 *
 * @author Thomas Jarrand <thomas.jarrand@gmail.com>
 */

namespace Stenope\Bundle\Provider\Factory;

use Stenope\Bundle\Provider\ContentProviderInterface;

/**
 * A factory to instantiate content providers based on type and config
 */
interface ContentProviderFactoryInterface
{
    public function create(string $type, array $config): ContentProviderInterface;

    public function supports(string $type, array $config): bool;
}
