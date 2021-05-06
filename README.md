# Stenope

> The static website generation tool for Symfony.

![CLI](cli.png)

## Why another static site generator?

We're Symfony developers that couldn't find a static website generator that bended to our needs.

We needed a way of generatating static websites that:

- Doesn't impose a format, a structure or a location for the data.
- Runs in a [Symfony](symfony.com) environement.
- Allow us to develop virtually any feature we might need.

Stenope does that.

## Installation

In your Symfony app:

    composer require stenope/stenope

## Usage

Just [install Stenope](#installation) and run:

    bin/console -e prod stenope:build ./static

Your Symfony app is now a static website in: `./static`! ✨

## Phylosophy and goals

Stenope was designed with these goals in mind:

- Stenope meets your needs, not the other way around.
- Stenope runs in any Symfony project out of the box, connects with standard Symfony components and feels natural to Symfony developers.
- Stenope is highly extensible: features can be replaced, added or removed.

## How it works

- 🔍 Stenope scans your Symfony app (like a search engine crawler would) and dumps every page into a static HTML file.
- 🛠 Stenope provides tools for loading and parsing various data sources (like local Markdown files or distant headless CMS).
- 🖌 Stenope enriches the parsed data by applying a serie of modificators (like Syntax Highlinting, slug generation, etc.).
- 🧲 Stenope finally hydrate your custom PHP objects with the enriched data and provides interfaces for listing and retrieving them (like an ORM would).
- ⚙️ Stenope gives you a lot of control over the whole process by providing entry points, interfaces and default implementations that are entirely replaceable.

## What Stenope is not

Stenope is not a ready-to-use bloging system: but you could quickly _write your own_ blog system with it!

## In-depth documentation

### Features

- [CLI usage](doc/cli.md)
- [Loading and parsing content](doc/loading-content.md)
- [Supported formats](doc/supported-formats.md)
- [Supported sources](doc/supported-sources.md) #TODO
- [Syntax highlighting](doc/syntax-highlighting.md)
- [Linking contents](doc/link-contents.md)
- [Configuring the build](doc/build-configuration.md)
- [Twig integration](doc/twig.md)

### Cookbooks

- [Specifying host and base url]() #TODO
- [Adding custom files to the build]() #TODO
- [Data source: writing a custom Provider]() #TODO
- [Data format: writing a custom Decoder]() #TODO
- [Data manipulation: writing a custom Processor]() #TODO
- [How to automatically deploy and host a static site]() #TODO
