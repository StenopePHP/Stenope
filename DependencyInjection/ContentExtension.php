<?php

/*
 * This file is part of the "Tom32i/Content" bundle.
 *
 * @author Thomas Jarrand <thomas.jarrand@gmail.com>
 */

namespace Content\DependencyInjection;

use Content\Behaviour\ContentDecoderInterface;
use Content\Behaviour\ContentDenormalizerInterface;
use Content\Behaviour\ContentProviderInterface;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;

class ContentExtension extends Extension
{
    public function load(array $configs, ContainerBuilder $container): void
    {
        $loader = new YamlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
        $loader->load('services.yaml');

        $container->registerForAutoconfiguration(ContentProviderInterface::class)->addTag('content.content_provider');
        $container->registerForAutoconfiguration(ContentDecoderInterface::class)->addTag('content.content_decoder');
        $container->registerForAutoconfiguration(ContentDenormalizerInterface::class)->addTag('content.content_denormalizer');
    }
}
