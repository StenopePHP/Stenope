<?php

/*
 * This file is part of the "Tom32i/Content" bundle.
 *
 * @author Thomas Jarrand <thomas.jarrand@gmail.com>
 */

namespace Content\DependencyInjection;

use Content\Behaviour\ProcessorInterface;
use Content\Builder;
use Content\Provider\ContentProviderInterface;
use Content\Provider\Factory\ContentProviderFactory;
use Content\Provider\Factory\ContentProviderFactoryInterface;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader\PhpFileLoader;
use Symfony\Component\DependencyInjection\Reference;

class ContentExtension extends Extension
{
    public function load(array $configs, ContainerBuilder $container): void
    {
        $loader = new PhpFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
        $loader->load('services.php');

        $container->registerForAutoconfiguration(ContentProviderFactoryInterface::class)->addTag('content.content_provider_factory');
        $container->registerForAutoconfiguration(ContentProviderInterface::class)->addTag('content.content_provider');
        $container->registerForAutoconfiguration(ProcessorInterface::class)->addTag('content.processor');

        $config = $this->processConfiguration($this->getConfiguration($configs, $container), $configs);

        $container->getDefinition(Builder::class)->replaceArgument('$buildDir', $config['build_dir']);
        $container->getDefinition(Builder::class)->replaceArgument('$filesToCopy', $config['copy']);

        $this->processProviders($container, $config['providers']);
    }

    public function getNamespace()
    {
        return 'http://tom32i.com/schema/dic/content';
    }

    public function getXsdValidationBasePath()
    {
        return __DIR__ . '/../Resources/config/schema';
    }

    private function processProviders(ContainerBuilder $container, array $providersConfig): void
    {
        foreach ($providersConfig as $class => ['type' => $type, 'config' => $config]) {
            $container->register("content.provider.$type.$class", ContentProviderInterface::class)
                ->setFactory([new Reference(ContentProviderFactory::class), 'create'])
                ->setArgument('$type', $type)
                ->setArgument('$config', ['class' => $class] + $config)
                ->addTag('content.content_provider')
            ;
        }
    }
}
