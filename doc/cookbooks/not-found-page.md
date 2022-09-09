# Exposing a not found page

Since Stenope is a static site generator, it does not handle 404 errors for missing pages.
Instead, your web server should handle them.

However, you can let Stenope generate a 404.html page for you, and let your web server serve it.

For instance, add in your `config/routes.yaml`:

```yaml
errors_404:
    path: 404.html
    controller: Symfony\Bundle\FrameworkBundle\Controller\TemplateController
    defaults:
        template: errors/404.html.twig
    options:
        stenope:
            sitemap: false
```

and create the `templates/errors/404.html.twig` template:

```twig
{% extends 'base.html.twig' %}

{% block title %}Page not found{% endblock %}

{% block content %}
    <h1>404 Not Found</h1>
    
    <p>The page you are looking for does not exist.</p>
{% endblock %}
```

Then, configure your web server to serve this page when a 404 error occurs.

Nginx:

```nginx
server {
    # […]
    
    location / {
        try_files $uri $uri/index.html =404;
    }
    location ~ \.html {
        internal;
    }
    
    error_page 404 /404.html;
```

Apache:

```apacheconf
ErrorDocument 404 /404.html
```

!!! note "404 page on Github Pages"
    If you're deploying on Github Pages, you're already set!
    The `404.html` file at the root of your `gh-pages` branch is automatically picked by Github for this purpose.
