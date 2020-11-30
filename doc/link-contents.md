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
provide a main route for the targeted type:

```php
# AuthorController.php

#[Route('author/{author}', name: 'show_author', options: [
    'stenope' => [
        'show' => [
            'class' => Author::class,
            'slug' => 'author',
        ],
    ],
])]
public function showAuthorAction(Author $author) { /* ... */ }
```

where:

- `stenope.show.class` is the content type being handled.
- `stenope.show.slug` is the controller argument name in which the slug should be injected.
