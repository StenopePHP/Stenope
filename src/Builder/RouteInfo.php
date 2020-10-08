<?php

/*
 * This file is part of the "Tom32i/Content" bundle.
 *
 * @author Thomas Jarrand <thomas.jarrand@gmail.com>
 */

namespace Content\Builder;

use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

/**
 * Route info
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
     * Is visible?
     */
    public function isVisible(): bool
    {
        return $this->route->getOption('visible') ?? $this->name[0] !== '_';
    }

    /**
     * Is Gettable
     */
    public function isGettable(): bool
    {
        $methods = $this->route->getMethods();

        return empty($methods) || \in_array('GET', $methods);
    }

    /**
     * Is route on sitemap?
     */
    public function isMapped(): bool
    {
        return $this->route->getOption('mapped') ?? $this->isVisible();
    }

    /**
     * Does the route relates to a content that might have dots in their slug?
     *
     * Set the `content.dots` option to true if some of the contents contains dots in their slug,
     * which might wrongly be considered as an extension for the generated page.
     */
    public function hasDotsSupport(): bool
    {
        return $this->route->getOption('content')['dots'] ?? false;
    }

    /**
     * Get format
     */
    public function getFormat(): string
    {
        return $this->route->getOption('_format') ?: 'html';
    }
}
