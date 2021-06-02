# CLI

## Build

The build command:

```shell
bin/console stenope:build [options] [--] [<buildDir>]
```

### Building into a specific directory

Use the `buildDir` argument:

```shell
bin/console stenope:build ./static
```

### Options

| Option | Description |
| -- | -- | -- |
| `--host=stenopephp.github.io` | What should be used as domain name for absolute url generation? | |
| `--base-url=/Stenope` | What should be used as base-url for absolute url generation? | |
| `--scheme=https` | What should be used as scheme for absolute url generation? | |
| `--no-sitemap` | Don't build the sitemap | |
| `--no-expose` | Don't expose the public directory | |
| `--ignore-content-not-found` | Ignore content not found errors | |

## Debug

There is a command to list, filter, sort out and display content managed by Stenope:

```shell
bin/console debug:stenope:content [options] [--] <class> [<id>]
```

E.g:

```shell
bin/console debug:stenope:content "App\Model\Article" --filter="not:outdated" --filter="slug contains:symfony" --order="desc:publishedAt"
```

```shell
bin/console debug:stenope:content "App\Model\Author" ogi
```

Use `--help` for more details and usage samples.
