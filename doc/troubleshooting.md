# Troubleshooting

## I have urls with an extension in their path which are rendered as `url/index.html` files

If you have some routes with an extension in their path (e.g: foo.pdf),
Stenope may attempt to render it as html at `foo.pdf/index.html`.

For the builder to understand such urls should be handled differently,
explicitly provide either the `format` option in your route definition:

```php
/**
* @Route("foo.pdf", name="foo_pdf", format="pdf")
*/
public function renderAsPdf() { /* ... */ }
```

or the `_format` request attribute / route default:

```php
/**
* @Route("foo.pdf", name="foo_pdf", defaults={ "_format": "pdf" })
*/
public function renderAsPdf(Request $request)
{
   // or directly through request attributes:
   $request->attributes->set('_format', 'pdf');
}
```

Which will result into the builder generating a `foo.pdf` file instead of `foo.pdf/index.html`.
