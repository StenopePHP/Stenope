<?php

/*
 * This file is part of the "StenopePHP/Stenope" bundle.
 *
 * @author Thomas Jarrand <thomas.jarrand@gmail.com>
 */

namespace Stenope\Bundle\Routing;

class ResolveContentRoute
{
    /** Route name */
    private string $route;

    /** Name of the slug placeholder */
    private string $slug;

    private array $defaults;

    public function __construct(string $route, string $slug, array $defaults = [])
    {
        $this->route = $route;
        $this->slug = $slug;
        $this->defaults = $defaults;
    }

    public function getRoute(): string
    {
        return $this->route;
    }

    public function getSlug(): string
    {
        return $this->slug;
    }

    public function getDefaults(): array
    {
        return $this->defaults;
    }
}
