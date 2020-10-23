<?php

/*
 * This file is part of the "StenopePHP/Stenope" bundle.
 *
 * @author Thomas Jarrand <thomas.jarrand@gmail.com>
 */

namespace Stenope\Bundle\Provider\Factory;

use Stenope\Bundle\Provider\ContentProviderInterface;
use Stenope\Bundle\Provider\LocalFilesystemProvider;

class LocalFilesystemProviderFactory implements ContentProviderFactoryInterface
{
    public const TYPE = 'files';

    public function create(string $type, array $config): ContentProviderInterface
    {
        return new LocalFilesystemProvider(
            $config['class'],
            $config['path'],
            $config['depth'] ?? null,
            $config['excludes'] ?? [],
            $config['patterns'] ?? ['*'],
        );
    }

    public function supports(string $type, array $config): bool
    {
        return self::TYPE === $type;
    }
}
