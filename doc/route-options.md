# Route options

This packages defines route options to control some of its features.

## Ignore a route

You may want to exclude some routes from the static generated version of your site.
In order to ignore such routes during the build, set the `content.ignore` option to `true`:

```php
/**
* @Route("foo", name="foo", options={
*     "content": {
*         "ignore": true,
*     },
* })
*/
public function fooAction() { /* ... */ }
```

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
