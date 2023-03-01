<?php

/*
 * This file is part of the "StenopePHP/Stenope" bundle.
 *
 * @author Thomas Jarrand <thomas.jarrand@gmail.com>
 */

namespace Stenope\Bundle\DependencyInjection;

use Stenope\Bundle\Provider\Factory\LocalFilesystemProviderFactory;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * @final
 */
class Configuration implements ConfigurationInterface
{
    private const NATIVE_PROVIDERS_TYPES = [
        LocalFilesystemProviderFactory::TYPE,
    ];

    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('stenope');
        $rootNode = method_exists(TreeBuilder::class, 'getRootNode') ? $treeBuilder->getRootNode() : $treeBuilder->root('stenope');

        $rootNode->children()
            ->scalarNode('build_dir')
                ->info('The directory where to build the static version of the app')
                ->cannotBeEmpty()
                ->defaultValue('%kernel.project_dir%/build')
            ->end()
            ->booleanNode('shared_html_crawlers')
                ->info('Activate the sharing of HTML crawlers for better performances.')
                ->defaultFalse()
            ->end()
            ->arrayNode('copy')
                ->defaultValue([
                    [
                        'src' => '%kernel.project_dir%/public',
                        'dest' => '.',
                        'fail_if_missing' => true,
                        'ignore_dot_files' => true,
                        'excludes' => ['*.php'],
                    ],
                ])
                ->example([
                    '%kernel.project_dir%/public/build',
                    '%kernel.project_dir%/public/robots.txt',
                    [
                        'src' => '%kernel.project_dir%/public/some-file-or-dir',
                        'dest' => 'to-another-dest-name',
                        'excludes' => ['*.php', '*.map'],
                        'fail_if_missing' => 'false',
                        'ignore_dot_files' => 'false',
                    ],
                ])
                ->arrayPrototype()
                    ->addDefaultsIfNotSet()
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
                        ->arrayNode('excludes')
                            ->fixXmlConfig('exclude')
                            ->defaultValue([])
                            ->info('List of files patterns to exclude')
                            ->beforeNormalization()->ifString()->castToArray()->end()
                            ->scalarPrototype()->end()
                        ->end()
                        ->scalarNode('fail_if_missing')
                            ->defaultTrue()
                            ->info('Make the build fail if the source file is missing')
                        ->end()
                        ->scalarNode('ignore_dot_files')
                            ->defaultTrue()
                            ->info('Whether to ignore or not dotfiles')
                        ->end()
                    ->end()
                ->end()
            ->end()
        ->end();

        $this->addProvidersSection($rootNode);
        $this->addResolveLinksSection($rootNode);
        $this->addProcessorsSection($rootNode);

        return $treeBuilder;
    }

    private function addResolveLinksSection(ArrayNodeDefinition $rootNode): void
    {
        $rootNode
            ->fixXmlConfig('resolve_link')
            ->children()
                ->arrayNode('resolve_links')
                    ->info('Indicates of to resolve a content type when a link to it is encountered inside anotehr content')
                    ->example([
                        'App\Content\Model\Recipe' => [
                            'route' => 'show_recipe',
                            'slug' => 'recipe',
                        ],
                    ])
                    ->useAttributeAsKey('class')
                    ->arrayPrototype()
                        ->children()
                            ->scalarNode('route')
                                ->example('show_recipe')
                                ->info('The name of the route to generate the URL')
                                ->isRequired()
                            ->end()
                            ->scalarNode('slug')
                                ->example('recipe')
                                ->info('The name of the route argument in which will be injected the content\'s slug')
                                ->isRequired()
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end()
        ;
    }

    private function addProvidersSection(ArrayNodeDefinition $rootNode): void
    {
        $rootNode
            ->fixXmlConfig('provider')
            ->children()
                ->arrayNode('providers')
                    ->example([
                        'App\Content\Model\Recipe' => '%kernel.project_dir%/content/recipes',
                        'App\Content\Model\Another' => [
                            '#type' => 'files # (default)',
                            'path' => '%kernel.project_dir%/content/recipes',
                            'patterns' => '*.md',
                        ],
                        'App\Content\Model\MyModelWithCustomProviderFactory' => [
                            'type' => 'custom_type',
                            'your-custom-key' => 'your-value',
                        ],
                    ])
                    ->useAttributeAsKey('class')
                    ->arrayPrototype()
                        ->beforeNormalization()
                            ->ifString()
                            ->then(static fn ($path) => ['config' => ['path' => $path]])
                        ->end()
                        ->beforeNormalization()
                            ->ifTrue(static fn ($p) => !isset($p['config']))
                            ->then(\Closure::fromCallable([$this, 'normalizeShortcutConfig']))
                        ->end()
                        ->beforeNormalization()
                            ->ifTrue(static fn ($p) => isset($p['type']) && !\in_array($p['type'], self::NATIVE_PROVIDERS_TYPES, true))
                            ->then(\Closure::fromCallable([$this, 'saveCustomProviderKeys']))
                        ->end()
                        ->children()
                            ->scalarNode('type')
                                ->example(self::NATIVE_PROVIDERS_TYPES)
                                ->info('The provider type used to fetch contents')
                                ->defaultValue(LocalFilesystemProviderFactory::TYPE)
                            ->end()
                            ->arrayNode('config')
                                ->normalizeKeys(false)
                                ->ignoreExtraKeys(false)
                                ->fixXmlConfig('pattern')
                                ->fixXmlConfig('exclude')
                                ->children()
                                    // Files provider
                                    ->scalarNode('path')->info('Required: The directory path for "files" providers')->end()
                                    ->scalarNode('depth')
                                        ->defaultNull()
                                        ->example('< 2')
                                        ->info(<<<INFO
                                            The directory depth for "files" providers.
                                            See "Symfony\Component\Finder\Finder::depth()"
                                            https://symfony.com/doc/current/components/finder.html#directory-depth
                                        INFO)
                                    ->end()
                                    ->arrayNode('patterns')
                                        ->defaultValue(['*'])
                                        ->info('The patterns to match for "files" providers')
                                        ->beforeNormalization()->ifString()->castToArray()->end()
                                        ->scalarPrototype()->end()
                                    ->end()
                                    ->arrayNode('excludes')
                                        ->info('The patterns to exclude for "files" providers')
                                        ->beforeNormalization()->ifString()->castToArray()->end()
                                        ->scalarPrototype()->end()
                                    ->end()
                                ->end()
                            ->end()
                        ->end()
                        ->validate()
                            ->always()
                            ->then(\Closure::fromCallable([$this, 'filterProviderConfig']))
                        ->end()
                        ->validate()
                            // Files provider validation
                            ->ifTrue(static fn ($p) => LocalFilesystemProviderFactory::TYPE === $p['type'] && empty($p['config']['path']))
                            ->thenInvalid('The "path" has to be specified to use the "files" provider')
                        ->end()
                    ->end()
                ->end()
            ->end()
        ;
    }

    private function addProcessorsSection(ArrayNodeDefinition $rootNode): void
    {
        $rootNode
            ->children()
                ->arrayNode('processors')
                    ->info('Built-in processors configuration. Disable to unregister all of the preconfigured processors.')
                    ->canBeDisabled()
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->scalarNode('content_property')
                            ->info('Key used by default by every processors to access and modify the main text of your contents')
                            ->defaultValue('content')
                            ->cannotBeEmpty()
                        ->end()

                        ->arrayNode('slug')
                            ->canBeDisabled()
                            ->children()
                                ->scalarNode('property')
                                    ->info('Inject the content slug in a property of your model. See SlugProcessor.')
                                    ->defaultValue('slug')
                                    ->cannotBeEmpty()
                                ->end()
                            ->end()
                        ->end()

                        ->arrayNode('assets')
                            ->info('Attempt to resolve local assets URLs using the Asset component for images and links. See AssetsProcessor.')
                            ->canBeDisabled()
                        ->end()

                        ->arrayNode('resolve_content_links')
                            ->info('Attempt to resolve relative links between contents using the route declared in config. See ResolveContentLinksProcessor.')
                            ->canBeDisabled()
                        ->end()

                        ->arrayNode('external_links')
                            ->info('Automatically add target="_blank" to external links. See HtmlExternalLinksProcessor.')
                            ->canBeDisabled()
                        ->end()

                        ->arrayNode('anchors')
                            ->canBeDisabled()
                            ->info('Automatically add anchor links to elements with an id. See HtmlAnchorProcessor.')
                            ->children()
                                ->scalarNode('selector')
                                    ->defaultValue('h1, h2, h3, h4, h5')
                                    ->cannotBeEmpty()
                                ->end()
                            ->end()
                        ->end()

                        ->arrayNode('html_title')
                            ->canBeDisabled()
                            ->info('Extract a content title from a HTML property by using the first available h1 tag. See ExtractTitleFromHtmlContentProcessor.')
                            ->children()
                                ->scalarNode('property')
                                    ->info('Property where to inject the title in your model')
                                    ->defaultValue('title')
                                    ->cannotBeEmpty()
                                ->end()
                            ->end()
                        ->end()

                        ->arrayNode('html_elements_ids')
                            ->canBeDisabled()
                            ->info('Add ids to titles, images and other HTML elements in the content. See HtmlIdProcessor.')
                        ->end()

                        ->arrayNode('code_highlight')
                            ->canBeDisabled()
                            ->info('Enabled the syntax highlighting for code blocks using Prism.js. See CodeHighlightProcessor.')
                        ->end()

                        ->arrayNode('toc')
                            ->canBeDisabled()
                            ->info('Build a table of content from the HTML titles. See TableOfContentProcessor.')
                            ->children()
                                ->scalarNode('property')
                                    ->info('Property used to configure and inject the TableOfContent object in your model')
                                    ->defaultValue('tableOfContent')
                                    ->cannotBeEmpty()
                                ->end()
                                ->integerNode('min_depth')
                                    ->defaultValue(2)
                                ->end()
                                ->integerNode('max_depth')
                                    ->defaultValue(6)
                                ->end()
                            ->end()
                        ->end()

                        ->arrayNode('last_modified')
                            ->canBeDisabled()
                            ->info('Attempt to fetch and populate the last modified date to a property. See LastModifiedProcessor.')
                            ->children()
                                ->scalarNode('property')
                                    ->info('Property where to inject the last modified date of the content according to its provider.')
                                    ->defaultValue('lastModified')
                                    ->cannotBeEmpty()
                                ->end()
                                ->arrayNode('git')
                                    ->canBeDisabled()
                                    ->info('Whether to attempt using Git to get the last modified date of the content according to commits.')
                                    ->children()
                                        ->scalarNode('path')
                                        ->info('Git binary path to use')
                                        ->defaultValue('git')
                                        ->cannotBeEmpty()
                                    ->end()
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end()
        ;
    }

    /**
     * Filters out provider config entries by factory type.
     */
    private function filterProviderConfig(array $provider): array
    {
        switch ($provider['type']) {
            case LocalFilesystemProviderFactory::TYPE:
                $keys = ['path', 'depth', 'patterns', 'excludes'];
                $provider['config'] = array_intersect_key($provider['config'], array_flip($keys));
                break;

            default:
                if ($keys = $provider['config']['_keys'] ?? false) {
                    // Only pass explicitly provided keys to userland factory:
                    $provider['config'] = array_intersect_key($provider['config'], array_flip($keys));
                }
        }

        return $provider;
    }

    /**
     * Save internally explicitly specified keys for userland provider factories.
     */
    private function saveCustomProviderKeys(array $provider): array
    {
        $provider['config']['_keys'] = array_keys($provider['config'] ?? []);

        return $provider;
    }

    /**
     * Normalizes shortcut config format to ['type' => ..., 'config' => [...]]
     */
    private function normalizeShortcutConfig(array $provider): array
    {
        $config = $provider;
        unset($config['type'], $config['config']);
        $normalized = ['config' => $config];

        if ($type = $provider['type'] ?? false) {
            $normalized['type'] = $type;
        }

        return $normalized;
    }
}
