# Content processors

Content processor are the main entry point for custom behaviours in Stenope.

## What are processors

A processor acts on the raw content loaded from your sources as an array, before denormalization into its model.

Writing a custom processor allow you to apply virtually any modification to the normalized content before denormalization.

Internally, Stenope registers default processors for:
- Syntax highlighting of code blocks.
- Add html attribute `id` to titles and images.
- Provide a `lastModified` property to contents based on their source last modification.
- ...
