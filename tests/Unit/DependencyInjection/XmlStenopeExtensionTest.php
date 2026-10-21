<?php

/*
 * This file is part of the "StenopePHP/Stenope" bundle.
 *
 * @author Thomas Jarrand <thomas.jarrand@gmail.com>
 * @author Maxime Steinhausser <maxime.steinhausser@gmail.com>
 */

namespace Stenope\Bundle\Tests\Unit\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;

/**
 * @group legacy
 */
class XmlStenopeExtensionTest extends StenopeExtensionTest
{
    protected function setUp(): void
    {
        if (!class_exists(XmlFileLoader::class)) {
            $this->markTestSkipped('Symfony >=8.0 dropped XML configuration');
        }
    }

    protected function loadFromFile(ContainerBuilder $container, string $file): void
    {
        $loader = new XmlFileLoader($container, new FileLocator(self::FIXTURES_PATH . '/xml'));
        $loader->load($file . '.xml');
    }
}
