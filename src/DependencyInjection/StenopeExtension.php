<?php

/*
 * This file is part of the "StenopePHP/Stenope" bundle.
 *
 * @author Thomas Jarrand <thomas.jarrand@gmail.com>
 */

namespace Stenope\Bundle\DependencyInjection;

use Stenope\Bundle\Behaviour\ProcessorInterface;
use Stenope\Bundle\Builder;
use Stenope\Bundle\Provider\ContentProviderInterface;
use Stenope\Bundle\Provider\Factory\ContentProviderFactory;
use Stenope\Bundle\Provider\Factory\ContentProviderFactoryInterface;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader\PhpFileLoader;
use Symfony\Component\DependencyInjection\Reference;

class StenopeExtension extends Extension
{
    public function load(array $configs, ContainerBuilder $container): void
    {
        $loader = new PhpFileLoader($container, new FileLocator(__DIR__ . '/../../config'));
        $loader->load('services.php');

        $container->registerForAutoconfiguration(ContentProviderFactoryInterface::class)->addTag('stenope.content_provider_factory');
        $container->registerForAutoconfiguration(ContentProviderInterface::class)->addTag('stenope.content_provider');
        $container->registerForAutoconfiguration(ProcessorInterface::class)->addTag('stenope.processor');

        $config = $this->processConfiguration($this->getConfiguration($configs, $container), $configs);

        $container->getDefinition(Builder::class)->replaceArgument('$buildDir', $config['build_dir']);
        $container->getDefinition(Builder::class)->replaceArgument('$filesToCopy', $config['copy']);

        $this->processProviders($container, $config['providers']);
    }

    public function getNamespace()
    {
        return 'http://stenope.com/schema/dic/stenope';
    }

    public function getXsdValidationBasePath()
    {
        return __DIR__ . '/../../config/schema';
    }

    private function processProviders(ContainerBuilder $container, array $providersConfig): void
    {
        foreach ($providersConfig as $class => ['type' => $type, 'config' => $config]) {
            $container->register("stenope.provider.$type.$class", ContentProviderInterface::class)
                ->setFactory([new Reference(ContentProviderFactory::class), 'create'])
                ->setArgument('$type', $type)
                ->setArgument('$config', ['class' => $class] + $config)
                ->addTag('stenope.content_provider')
            ;
        }
    }
}
