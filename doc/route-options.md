# Route options

This packages defines route options to control some of its features.

## Ignore a route

You may want to exclude some routes from the static generated version of your site.
In order to ignore such routes during the build, set the `content.visible` option to `false`:

```php
/**
* @Route("foo", name="foo", options={ 
*     "content": { 
*         "visible": false,
*     },
* })
*/
public function fooAction() { /* ... */ }
```

Alternatively, a route name starting with a `.` is considered not visible to the static site builder.

## Exclude a route from sitemap

If you need to exclude some routes from the generated sitemap,
set the `content.sitemap` route option to `false`:

```php
/**
* @Route("foo", name="foo", options={ 
*     "content": { 
*         "sitemap": false,
*     },
* })
*/
public function fooAction() { /* ... */ }
```
