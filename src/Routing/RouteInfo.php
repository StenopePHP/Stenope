<?php

/*
 * This file is part of the "StenopePHP/Stenope" bundle.
 *
 * @author Thomas Jarrand <thomas.jarrand@gmail.com>
 */

namespace Stenope\Bundle\Routing;

use Stenope\Bundle\Builder;
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
     * @return array<string,RouteInfo>
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
     * Whether it should be be ignored by the static site builder or not.
     * If true, the route will be ignored by the {@link Builder},
     * which won't generate a static file for matching urls.
     */
    public function isIgnored(): bool
    {
        return $this->route->getOption('stenope')['ignore'] ?? false;
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
        return $this->route->getOption('stenope')['sitemap'] ?? !$this->isIgnored();
    }

    public function getRoute(): Route
    {
        return $this->route;
    }
}
