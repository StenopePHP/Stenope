<?php

/*
 * This file is part of the "Tom32i/Content" bundle.
 *
 * @author Thomas Jarrand <thomas.jarrand@gmail.com>
 */

namespace Content\DependencyInjection;

use Content\Behaviour\ContentProviderInterface;
use Content\Builder;
use Content\ContentManager;
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

        $config = $this->processConfiguration($this->getConfiguration($configs, $container), $configs);

        $container->getDefinition(ContentManager::class)->replaceArgument('$path', $config['content_dir']);

        $container->getDefinition(Builder::class)->replaceArgument('$buildDir', $config['build_dir']);
        $container->getDefinition(Builder::class)->replaceArgument('$filesToCopy', $config['copy']);
    }

    public function getNamespace()
    {
        return 'http://tom32i.com/schema/dic/content';
    }

    public function getXsdValidationBasePath()
    {
        return __DIR__ . '/../Resources/config/schema';
    }
}
