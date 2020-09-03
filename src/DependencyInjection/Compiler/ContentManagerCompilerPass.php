<?php

/*
 * This file is part of the "Tom32i/Content" bundle.
 *
 * @author Thomas Jarrand <thomas.jarrand@gmail.com>
 */

namespace Content\DependencyInjection\Compiler;

use Content\ContentManager;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class ContentManagerCompilerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        $definition = $container->findDefinition(ContentManager::class);

        // Content providers
        foreach ($container->findTaggedServiceIds('content.content_provider') as $id => $tags) {
            $definition->addMethodCall('addContentProvider', [new Reference($id)]);
        }

        // Property handlers
        foreach ($container->findTaggedServiceIds('content.property_handler') as $id => $tags) {
            foreach ($tags as $tag) {
                if (!isset($tag['property'])) {
                    throw new \Exception(sprintf('No property specified for property handler "%s".', $id));
                }

                $definition->addMethodCall('addPropertyHandler', [$tag['property'], new Reference($id)]);
            }
        }
    }
}
