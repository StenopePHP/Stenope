<?php

/*
 * This file is part of the "StenopePHP/Stenope" bundle.
 *
 * @author Thomas Jarrand <thomas.jarrand@gmail.com>
 */

namespace Stenope\Bundle\Routing;

use Stenope\Bundle\Content;
use Symfony\Component\Routing\RouterInterface;

class ContentUrlResolver
{
    private RouterInterface $router;

    /** @var ResolveContentRoute[] */
    private array $routes;

    /**
     * @param array<string,ResolveContentRoute> $routes
     */
    public function __construct(RouterInterface $router, array $routes)
    {
        $this->router = $router;
        $this->routes = $routes;
    }

    public function resolveUrl(Content $content): string
    {
        $resolved = $this->resolveRoute($content);

        return $this->router->generate($resolved->getRoute(), [
            $resolved->getSlug() => $content->getSlug(),
        ]);
    }

    private function resolveRoute(Content $content): ResolveContentRoute
    {
        if ($resolved = $this->routes[$content->getType()] ?? null) {
            return $resolved;
        }

        throw new \LogicException(sprintf(
            'No route was defined to resolve type "%s". Did you configure "stenope.resolve_links" for this type?',
            $content->getType(),
        ));
    }
}
