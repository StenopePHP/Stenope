<?php

/*
 * This file is part of the "Tom32i/Content" bundle.
 *
 * @author Thomas Jarrand <thomas.jarrand@gmail.com>
 */

namespace Content\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder('content');
        $rootNode = method_exists(TreeBuilder::class, 'getRootNode') ? $treeBuilder->getRootNode() : $treeBuilder->root('content');

        $rootNode->children()
            ->scalarNode('content_dir')
                ->info('The base directory where to search for local content used to generate the pages')
                ->cannotBeEmpty()
                ->defaultValue('%kernel.project_dir%/content')
            ->end()
            ->scalarNode('build_dir')
                ->info('The directory where to build the static version of the app')
                ->cannotBeEmpty()
                ->defaultValue('%kernel.project_dir%/build')
            ->end()
            ->arrayNode('copy')
                ->example([
                    '%kernel.project_dir%/public/build',
                    '%kernel.project_dir%/public/robots.txt',
                    [
                        'src' => '%kernel.project_dir%/public/some-file-or-dir',
                        'dest' => 'to-another-dest-name',
                        'fail_if_missing' => 'false',
                    ],
                ])
                ->arrayPrototype()
                    ->beforeNormalization()
                        ->ifString()
                        ->then(static fn (string $v) => ['src' => $v, 'dest' => basename($v)])
                    ->end()
                    ->validate()
                        ->ifTrue(static fn (array $v) => !isset($v['dest']))
                        ->then(static function (array $v) {
                            $v['dest'] = basename($v['src']);

                            return $v;
                        })
                    ->end()
                    ->children()
                        ->scalarNode('src')
                            ->info('Full source path to the file/dir to copy')
                            ->cannotBeEmpty()
                        ->end()
                        ->scalarNode('dest')
                            ->defaultNull()
                            ->info('Destination path relative to the configured build_dir. If null, defaults to the same name as source.')
                        ->end()
                        ->scalarNode('fail_if_missing')
                            ->defaultTrue()
                            ->info('Make the build fail if the source file is missing')
                        ->end()
                    ->end()
                ->end()
            ->end()
        ->end();

        return $treeBuilder;
    }
}
