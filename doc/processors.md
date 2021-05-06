# Content processors

Content processor are the main entry point for custom behaviours in Stenope.

## What are processors

Processors intervene in the content loading process, after the raw content is parsed and normalized into an array, and before it's hydrated into a model object.

Writing a custom processor allow you to aply virtualiy any modification to the normalized content before it's returned as an object.

Internaly, Stenope has processors for:
- Highlighting code blocks with synthaxic coloration.
- Add html attribute `id` to titles and images.
- Provide a `lastModified` property to contents based on their source last modification.
- ...
