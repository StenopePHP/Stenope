# Route options

This package defines route options to control some of its features.

## Ignore a route

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

## Exclude a route from sitemap

If you need to exclude some routes from the generated sitemap,
set the `stenope.sitemap` route option to `false`:

```php
#[Route('foo', name: 'foo', options: [
    'stenope' => [
        'sitemap' => false,
    ],
])]
public function fooAction() { /* ... */ }
```

## Declare a main route for showing a content type

You can declare a route as being the main one to use for rendering contents when creating links to them:

```php
#[Route('recipe/{recipe}', name: 'show_recipe', options: [
    'stenope' => [
        'show' => [
            'class' => Recipe::class,
            'slug' => 'recipe',
        ],
    ],
])]
public function showRecipeAction() { /* ... */ }
```

See [link static contents between them](./link-contents.md)
