# Stenope

> Export your Symfony app as a static website.

Stenope is a **static website building tool for Symfony** with specific goals:
- "You should adapt it to your need, don't adapt your needs to it".
- "It connects with standard Symfony components and feels natural to Symfony developers".

## How it works

- 🔍 Stenope scans your Symfony app, like a search engine crawler would, and dumps every page to static HTML.
- 🛠 Stenope provides tools that you can use in _your_ Symfony code to load and parse static contents (such as Markdown files) into custom PHP model objects.
- ⚙️ Stenope gives you a lot of control by providing interfaces and default implementations that are entirely replaceable to suit your custom needs.

## Installation

    composer require stenope/stenope

## Usage

Build the static site: `bin/console -e prod stenope:build`

✨ Your Symfony app is now a static website in: `/build`!


## Advanced usage

- [How to load static content](doc/loading-content.md)
- [Supported formats](doc/supported-formats.md)
- [Syntax Highlighting](doc/syntax-highlighting.md)
- [Route options](doc/route-options.md)
- [Troubleshooting and common mistakes](doc/troubleshooting.md)
- Decoders #TODO
- Bonus: How to deploy and host a static site #TODO
