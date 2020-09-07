<?php

/*
 * This file is part of the "Tom32i/Content" bundle.
 *
 * @author Thomas Jarrand <thomas.jarrand@gmail.com>
 */

namespace Content\Tests\Unit\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;

class XmlContentExtensionTest extends ContentExtensionTest
{
    protected function loadFromFile(ContainerBuilder $container, string $file): void
    {
        $loader = new XmlFileLoader($container, new FileLocator(self::FIXTURES_PATH . '/xml'));
        $loader->load($file . '.xml');
    }
}
