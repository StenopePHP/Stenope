<?php

/*
 * This file is part of the "Tom32i/Content" bundle.
 *
 * @author Thomas Jarrand <thomas.jarrand@gmail.com>
 */

namespace Content\Tests\Unit\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;

class YamlContentExtensionTest extends ContentExtensionTest
{
    protected function loadFromFile(ContainerBuilder $container, string $file): void
    {
        $loader = new YamlFileLoader($container, new FileLocator(self::FIXTURES_PATH . '/yaml'));
        $loader->load($file . '.yaml');
    }
}
