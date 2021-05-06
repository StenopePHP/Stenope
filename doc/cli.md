# CLI

The build command:

```shell
bin/console stenope:build [options] [--] [<buildDir>]
```

## Building into a specific directory

Use the `buildDir` argument:

```shell
bin/console stenope:build ./static
```

## Options

| Option | Description |
| -- | -- | -- |
| `--host=stenopephp.github.io` | What should be used as domain name for absolute url generation? | |
| `--base-url=/Stenope` | What should be used as base-url for absolute url generation? | |
| `--scheme=https` | What should be used as scheme for absolute url generation? | |
| `--no-sitemap` | Don't build the sitemap | |
| `--no-expose` | Don't expose the public directory | |
| `--ignore-content-not-found` | Ignore content not found errors | |
