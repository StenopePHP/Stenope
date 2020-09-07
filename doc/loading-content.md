# Loading static content

Let's do a simple blog.

## Setup

### Create the model

Static content rely on model classes (just like Doctrine entities).
This can be a simple DTO and doesn't need to follow any rule:

```php
<?php

namespace App\Model;

class Article {
    public string $title;
    public string $slug;
    public string $content;
    public \DateTimeInterface $created;
    public \DateTimeInterface $lastModified;
}
```

### Register a content provider

The content Provider is responsible for telling Content where to look for your content static sources files.

Create a service that implements `Content\Behaviour\ContentProviderInterface` and support your model:

```php
<?php

namespace App\Content\Provider;

use App\Model\Article;
use Content\Behaviour\ContentProviderInterface;

class ArticleProvider implements ContentProviderInterface
{
    /**
     * Must return true for supported models.
     */
    public function supports(string $className): bool
    {
        return is_a($className, Article::class, true);
    }

    /**
     * Where to load content from root content directory (usually /content).
     */
    public function getDirectory(): string
    {
        return 'article';
    }
}
```

## Usage

### Listing content

In your controller (or service):

```php
<?php

namespace App\Controller;

use App\Model\Article;
use Content\ContentManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/blog")
 */
class BlogController extends AbstractController
{
    /**
     * @Route("/", name="blog")
     */
    public function index(ContentManager $contentManager)
    {
        return $this->render(
            'blog/index.html.twig',
            ['articles' => $contentManager->getContents(Article::class)]
        );
    }
```

### Fetching a specific content

The ContentManager uses slugs to identify your content. The `slug` argument must exactly matche the static file name in your content directory.

Example: `$contentManager->getContent(Article::class, 'how-to-train-your-dragon');` will fetch the `content/article/how-to-train-your-dragon.md` article.

```php
<?php

namespace App\Controller;

use App\Model\Article;
use Content\ContentManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/blog")
 */
class BlogController extends AbstractController
{
    // ...

    /**
     * @Route("/{slug}", name="article")
     */
    public function article(ContentManager $contentManager, string $slug)
    {
        return $this->render(
            'blog/article.html.twig',
            ['article' => $contentManager->getContent(Article::class, $slug)]
        );
    }
```

### Sorting contents

The `getContents` method have a second parameters `$sortBy` that allow sorting the content list.

This argument accepts:

- An string: `'lastModified'` (sort by ascending values of the "lastModified" property).
- An array: `['title' => false]` (sort by descending values of the "title" property).
- A comparison callable for the PHP [usort](https://www.php.net/manual/fr/function.usort.php) function: `fn($a, $b) => $a->priority <=> $b->priority`.

When provided, the ContentManager will list all content and sort the array with the corresponding sorting function before returning it.

## Advanced usage and extention

### Register a custom denormalizer

For more control over your model denormalization, you can register your own Denormalizer.
Simply, create a service that implements Symfony's `DenormalizerInterface` and supports your model:

```php
<?php

namespace App\Content\Denormalizer;

use App\Model\Article;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

class ArticleDenormalizer implements DenormalizerInterface
{
    /**
     * Must return true for supported models.
     */
    public function supportsDenormalization($data, $type, $format = null)
    {
        return is_a($type, Article::class, true);
    }

    /**
     * Instanciate your model from the raw data array.
     */
    public function denormalize($data, $class, $format = null, array $context = [])
    {
        return new Article(
            $data['title'],
            $data['slug'],
            $data['content'],
            new \DateTimeImmutable($data['date']),
            new \DateTimeImmutable($data['lastModified'])
        );
    }
}
```

_Note: Using autowiring, denormalizers are automaticaly registered in Symfony Serializer._
