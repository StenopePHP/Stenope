# Synthax highlighting

## Setting the language attribute

Any `code` block with a `language` attribute will be synthax highlighted.

### Markdown

In Markdown, specify the language in the opening line will:

```markdown
​```php
<?php $foo = true;
​```
```

### HTML

In HTML, specify the language in the `class` attribute, like this:

```html
​<code class="language-php">
<?php $foo = true;
<code>
```

_Note: since Markdown also supports embeded HMTL, the above code block will also work in a markdown source file._

## Result

This will result in the following HTML block in the parsed content:

```html
<pre class="code-multiline">
    <code class="language-php" id="1b7716ebf50e565f2f76a0de64362190">
        <span class="token php language-php"><span class="token delimiter important">&lt;?php</span> <span class="token variable">$foo</span> <span class="token operator">=</span> <span class="token boolean constant">true</span><span class="token punctuation">;</span></span>
    </code>
</pre>
```

_Note: providing a CSS theme that color the code is entirely up to you. You can use any of the official [Prism.js](https://prismjs.com) theme out of the box just by loading the CSS file._

## Custom synthax highlighting

The default Highlighter responsible for syntax highlighting is based on [Prism.js](https://prismjs.com/), but you can provide your own by implementing `Content\Behaviour\HighlighterInterface`:

```php
public function highlight(string $value, string $language): string;
```
