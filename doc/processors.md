# Content processors

Content processor are the main entry point for custom behaviours in Stenope.

## What are processors

A processor acts on the raw content loaded from your sources as an array, before denormalization into its model.

Writing a custom processor allow you to apply virtually any modification to the decoded content before denormalization.

Internally, Stenope registers default processors for:
- Syntax highlighting for code blocks.
- Adding `id` html attribute to titles and images.
- Providing a `lastModified` property to contents based on their source last modification.
- ...

## Writing a custom processor

To write your own processor, just implements the `Stenope\Bundle\Behaviour\ProcessorInterface` interface:

```php
<?php

namespace App\Stenope\Processor;

use App\Model\User;
use Stenope\Bundle\Behaviour\ProcessorInterface;
use Stenope\Bundle\Content;

/**
 * Load avatar from Gravatar for users
 */
class GravatarProcessor implements ProcessorInterface
{
    /**
     * Apply modifications to decoded data before denormalization
     *
     * @param array   $data    The decoded data
     * @param string  $type    The model being processed (FQN)
     * @param Content $content The source content
     */
    public function __invoke(array &$data, string $type, Content $content): void
    {
        // Only apply this processor on Users
        if (!is_a($type, User::class, true)) {
            return;
        }

        // Ingore if url is already explicitly specified
        if (\array_key_exists('avatar', $data)) {
            return;
        }

        // Generate Gravatar url from email address
        $data['avatar'] = 'https://www.gravatar.com/avatar/' . md5(strtolower(trim($data['email'])));
    }
}
```

That's it!

_Note: See Stenope's [own processors](https://github.com/StenopePHP/Stenope/tree/master/src/Processor) for more examples._

## Ordering processor

Processor are applied one after another, and a Processor **A** can modify the `$data` that will then be used by a processor **B**.

To control processor execution order, use the `priority` tag property:

```yaml
App\Stenope\Processor\TableOfContentProcessor:
        tags: [{ name: stenope.processor, priority: -10 }]
```
_Note: High priority are executed first, lower priority (typically negative ones) are executed last._

