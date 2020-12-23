# Link static contents between them

Given the following structure:

```treeview
content/
├── recipes/
|   ├── ogito.md
|   └── cheesecake.md
└── authors/
    ├── ogi.md
    └── tom32i.md
```

where the recipes and authors contents are respectively handled with `Recipe` 
and `Authors` types and rendered by their own controllers.

Such an `ogito.md` recipe

```markdown
# The Ogito recipe

Fiscina de domesticus amicitia, pugna vortex [...]

Cheers [Ogi](../authors/ogi.md) for this recipe.
```

attempts to reference another content entry in order to display an HTML link to it.

Stenope is natively able to resolve such relative links, but requires you to 
provide a main route for the targeted type to resolve.

Considering you want to use this route:

```php
#[Route('author/{author}', name: 'show_author')]
public function showAuthorAction(Author $author) { /* ... */ }
```

the config allowing to resolve such links will be:

```php
# config/packages/stenope.yaml
stenope:
    resolve_links:
        App\Model\Author: { route: 'show_author', slug: 'author' }
```

where:

- `route` is the route to use for resolving the link.
- `slug` is the name of the controller argument in which the content slug should be injected.
