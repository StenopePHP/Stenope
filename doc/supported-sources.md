# Supported sources

## Embeded providers

### Local file system

The `LocalFilesystemProvider` is based on Symfony's Finder component and allows you to load contents from the local file system by attributing a path to a content type.

To use it, specify a provider of type `files`:

```yaml
stenope:
    providers:
        App\Model\Recipe:
            type: 'files'
            path: '%kernel.project_dir%/content/recipes'
```

Since the local file system provider is the default provider, you can ommit it in configuration like this:

```yaml
stenope:
    providers:
        App\Model\Recipe: '%kernel.project_dir%/content/recipes'
        App\Model\Cook: '%kernel.project_dir%/content/cooks'
```

## Load same content from different sources

Same kind of content can come from different sources, you just have to declare several names providers for this model:

```yaml
stenope:
    providers:
        local_recipes:
            type: 'files'
            class: 'App\Model\Recipe'
        distant_recipes:
            type: 'my_distant_provider'
            class: 'App\Model\Recipe'
```

Advanced configuration:

```yaml
stenope:
    providers:
        App\Model\Recipe:
            path: '%kernel.project_dir%/content/recipes'
            patterns: '*.md' # Only load .md files
            depth: '< 2' # https://symfony.com/doc/current/components/finder.html#directory-depth
        App\Model\Cook:
            path: '%kernel.project_dir%/content/recipes'
            exclude:
                - sample.*
                - *.php
