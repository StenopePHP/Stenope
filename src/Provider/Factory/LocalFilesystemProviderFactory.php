<?php

/*
 * This file is part of the "Tom32i/Content" bundle.
 *
 * @author Thomas Jarrand <thomas.jarrand@gmail.com>
 */

namespace Content\Provider\Factory;

use Content\Provider\ContentProviderInterface;
use Content\Provider\LocalFilesystemProvider;

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
