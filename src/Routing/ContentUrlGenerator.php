<?php

/*
 * This file is part of the "StenopePHP/Stenope" bundle.
 *
 * @author Thomas Jarrand <thomas.jarrand@gmail.com>
 */

namespace Stenope\Bundle\Routing;

use Stenope\Bundle\Content;
use Symfony\Component\Routing\RouterInterface;

class ContentUrlGenerator
{
    private RouterInterface $router;

    /** @var RouteInfo[] */
    private array $routes = [];

    public function __construct(RouterInterface $router)
    {
        $this->router = $router;
    }

    public function generate(Content $content): string
    {
        $routeInfo = $this->getRouteInfo($content);

        return $this->router->generate($routeInfo->getName(), [
            $routeInfo->getMainContentRouteSlugVariable() => $content->getSlug(),
        ]);
    }

    private function getRouteInfo(Content $content): RouteInfo
    {
        if (!$this->routes) {
            // Using `$router->getRouteCollection()` is not recommended at runtime.
            // Might be worth adding a cache warmer to collect the info in cache.
            $this->routes = RouteInfo::createFromRouteCollection($this->router->getRouteCollection());
        }

        foreach ($this->routes as $routeInfo) {
            if ($routeInfo->isMainContentRoute($content->getType())) {
                return $routeInfo;
            }
        }

        throw new \LogicException(sprintf('No main route was defined for type "%s"', $content->getType()));
    }
}
