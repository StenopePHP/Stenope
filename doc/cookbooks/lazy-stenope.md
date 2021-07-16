# Lazy Stenope

Lazy or Micro Stenope is a lightweight opinionated alternative on how to create
a Symfony application for generating a static site, with minimal knowledge, code
and requirements.  

Most notably, for basic usages, it's only required to know about Twig
(templating), but **you don't need to write custom code to serve your content**.  
Start right away by writing the content in the format you like, write the
templates to render them and map the pages together, so it'll be dumped and
accessible from the static generated version.

It makes Stenope an alternative comparable to some other generation tools like
Hugo, **where you only need to write content and templates**, while still being
able to feel at home and tweak your app with your Symfony's knowledge if needed.

## How to start

Right after creating a Symfony app and requiring Stenope using Composer,
configure the Lazy Stenope stack in your `src/Kernel.php`:

```diff
<?php
namespace App;

+use Stenope\Bundle\MicroStenopeKernelTrait;
use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
// [...]

class Kernel extends BaseKernel
{
    use MicroKernelTrait;
+    use MicroStenopeKernelTrait;

    protected function configureContainer(ContainerConfigurator $container): void
    {
        // [...]
+        $this->configureStenope($container);
    }

    protected function configureRoutes(RoutingConfigurator $routes): void
    {
        // [...]
+        $this->configureStenopeRoutes($routes);
    }
}

```

It'll register two routes for showing and listing contents:

| Route | Path (default) | Description |
| - | - | - |
| `stenope_show` | `/{slug}` | Show a specific content |
| `stenope_list` | `/{type}/list` | List all contents of a given type and sub-types |

as well as preconfiguring Stenope to load contents from a `content` directory on
your filesystem.  
The equivalent config is:

```yaml
stenope:
    providers:
        Stenope\Bundle\Content\GenericContent: '%kernel.project_dir%/content'
    resolve_links:
        Stenope\Bundle\Content\GenericContent: { route: stenope_show, slug: slug }
```

Since your application would need an entrypoint, you can create a basic home
route and template using:

```yaml
# config/routes.yaml
home:
  path: /
  controller: Symfony\Bundle\FrameworkBundle\Controller\TemplateController
  defaults:
    template: home.html.twig
```

and the following templates structures:

```treeview
templates/
├── base.html.twig # base layout for your site
└── home.html.twig
```

## Writing contents

You can write content in [any supported format](../supported-formats.md), with
any data inside ; on the contrary of the "full-stack" Stenope approach, there is
no truly enforced structured model to which will be mapped your content.  
Instead, a `GenericContent` is used and accepts any property.  
Any structured data you write in your content is mapped to a property defined at
runtime on this content object.

E.g, such content:

```md
---
title: "Sunt amicitiaes desiderium ferox, placidus liberies."   
date: 2021-09-10  
draft: true
---

Always cosmically develop the wonderful sinner.
```

will instantiate a `GenericContent` object with `title`, `date` and `draft`
properties from the markdown header metadata. Specific to
the [markdown format](../supported-formats.md#markdown), the `content` property
is created as well, using the main file content.

Additionally, it always defines these 3 properties:


| property | description |
| - | - |
| `slug` | the content identifier, i.e: its file path. |
| `type` | the content main type, i.e: its folder (e.g: a `users/john.md` content is of type `users`) |
| `types` | the content types, i.e: its folder hierarchy (e.g: a `users/legacy/sarah.md` content has types `users/legacy` and `users`) |

## Writing templates

Lazy-Stenope discovers the template files to use for your contents by
convention.

### Show templates

At first, it'll attempt to find a template matching the exact directory
structure of your content, so a `users/john.md` content could be rendered using
a `templates/users/john.html.twig` template.

If this template is not found, it'll attempt to look at the content type(s) and
search for a `show.html.twig` template. This way, you can use a same template to
render similar types of contents, based on the directory structure and
organization of your contents.

Eventually, if no matching template is found so far, it'll make a last attempt
to load a generic `templates/stenope/show.html.twig` template.

You can also explicit the template to use for a content using the `template`
property:

```md
---
template: 'other/an-explicit-template.html.twig'  
title: "Sunt amicitiaes desiderium ferox, placidus liberies."
---

Always cosmically develop the wonderful sinner.
```

To sum up, it'll attempt to load a template in the following order:

1. Explicit template path from the `template` property
1. `templates/{slug}.html.twig`
1. `templates/{type}/show.html.twig`
1. `templates/{parentType}/show.html.twig`
1. `templates/stenope/show.html.twig`

#### Available variables

The following variables are exposed to the template:

| variable | description |
| - | - |
| `content` | the `GenericContent` object instantiated from your content file |

#### Generate a link

You can generate a link to a content using its slug and:

```twig
<a href="{{ path('stenope_show', { slug: 'users/john' }) }}">John's profile</a>
```

### List templates

Contents inside a directory structure have one or more types registered, which
you can use to categorize and differentiate some common types of contents (e.g:
a `users/legacy/sarah.md` content has types `users/legacy` and `users`).

Typed contents can be listed on a page by rendering
a `templates/{type}/list.html.twig`
template, so contents inside a `users` directory could be rendered using
a `templates/users/list.html.twig` template.

Eventually, if no matching template is found so far, it'll make a last attempt
to load a generic `templates/stenope/show.html.twig` template.

To sum up, it'll attempt to load a template in the following order:

1. `templates/{type}/list.html.twig`
1. `templates/stenope/show.html.twig`

#### Available variables

The following variables are exposed to the template:

| variable | description |
| - | - |
| `type` | current type in the URL |
| `types` | the types hierarchy for the type in the URL |
| `contents` | the objects instantiated from your content files, filtered by the type in the URL |

#### Generate a link

You can generate a link to a content using its slug and:

```twig
<a href="{{ path('stenope_list', { type: 'users' }) }}">List users</a>
```

### Template directory structure reference

```treeview
templates/
├── base.html.twig
├── projects.html.twig ➜ renders the `projects.{yaml,json,md,…}` content
├── home.html.twig
├── stenope/
│   ├── list.html.twig ➜ renders a listing for a type with no matching list template
│   └── show.html.twig ➜ render a content without matching template
└── users/
    ├── legacy/
    │   ├── list.html.twig ➜ renders a listing for a content from the `users/legacy` directory
    │   └── show.html.twig ➜ renders a content from the `users/legacy` directory
    ├── list.html.twig ➜ renders a listing for a content from the `users` directory
    └── show.html.twig ➜ renders a content from the `users` directory
```
