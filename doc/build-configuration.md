# Configuring the build

## Setting the build directory

The build directory is the folder in which all the static content gets dumped.

Their's several way to tell Stenope where to dump the static files:

### In global configuration

```yaml
# config/packages/stenope.yaml
stenope:
    build_dir: '%kernel.project_dir%/build'
```

### From command line argument

```shell
bin/console stenope:build ./path/to/the/build
```

## Controlling wich content gets in the build

This package defines route options to control some of its features.

### Ignoring a route

You may want to exclude some routes from the static generated version of your site.

In order to ignore such routes during the build, set the `stenope.ignore` option to `true`:

```php
#[Route('foo', name: 'foo', options: [
    'stenope' => [
        'ignore' => true,
    ],
])]
public function fooAction() { /* ... */ }
```

### Excluding a route from the sitemap

If you need to exclude some routes from the generated sitemap, but keep them in the build anyway, set the `stenope.sitemap` route option to `false`:

```php
#[Route('foo', name: 'foo', options: [
    'stenope' => [
        'sitemap' => false,
    ],
])]
public function fooAction() { /* ... */ }
```
