<?php

/*
 * This file is part of the "Tom32i/Content" bundle.
 *
 * @author Thomas Jarrand <thomas.jarrand@gmail.com>
 */

namespace Content\Builder;

use Content\Builder;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

/**
 * Extracts content specific info from route definitions.
 */
class RouteInfo
{
    private string $name;
    private Route $route;

    public function __construct(string $name, Route $route)
    {
        $this->name = $name;
        $this->route = $route;
    }

    /**
     * @return RouteInfo[]
     */
    public static function createFromRouteCollection(RouteCollection $collection): array
    {
        $routes = [];

        foreach ($collection as $name => $route) {
            $routes[$name] = new self($name, $route);
        }

        return $routes;
    }

    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Whether it should be exposed to the static site builder or not.
     * If not visible, the route will be ignored by the {@link Builder},
     * which won't generate a static file for matching urls.
     */
    public function isVisible(): bool
    {
        return $this->route->getOption('content')['visible'] ?? $this->name[0] !== '.';
    }

    /**
     * Whether the route accepts GET requests or not.
     */
    public function isGettable(): bool
    {
        $methods = $this->route->getMethods();

        return empty($methods) || \in_array('GET', $methods);
    }

    /**
     * Whether to expose or not the route in the generated sitemap.
     */
    public function isMapped(): bool
    {
        return $this->route->getOption('content')['sitemap'] ?? $this->isVisible();
    }
}
