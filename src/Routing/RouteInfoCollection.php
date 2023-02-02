<?php

/*
 * This file is part of the "StenopePHP/Stenope" bundle.
 *
 * @author Thomas Jarrand <thomas.jarrand@gmail.com>
 */

namespace Stenope\Bundle\Routing;

use Symfony\Component\Routing\RouterInterface;

/**
 * @phpstan-implements \IteratorAggregate<string,RouteInfo>
 *
 * @final
 */
class RouteInfoCollection implements \IteratorAggregate, \ArrayAccess, \Countable
{
    private RouterInterface $router;

    /** @var array<string,RouteInfo>|null */
    private ?array $routeInfos = null;

    public function __construct(RouterInterface $router)
    {
        $this->router = $router;
    }

    /**
     * @return array<string,RouteInfo>
     */
    private function getInfos(): array
    {
        if (!$this->routeInfos) {
            $this->routeInfos = RouteInfo::createFromRouteCollection($this->router->getRouteCollection());
        }

        return $this->routeInfos;
    }

    public function getIterator(): \Traversable
    {
        return new \ArrayIterator($this->getInfos());
    }

    public function offsetExists($offset): bool
    {
        return isset($this->getInfos()[$offset]);
    }

    public function offsetGet($offset): ?RouteInfo
    {
        return $this->getInfos()[$offset] ?? null;
    }

    public function offsetSet($offset, $value): void
    {
        throw new \BadMethodCallException(sprintf('Unexpected call to "%s()"', __METHOD__));
    }

    public function offsetUnset($offset): void
    {
        throw new \BadMethodCallException(sprintf('Unexpected call to "%s()"', __METHOD__));
    }

    public function count(): int
    {
        return \count($this->getInfos());
    }
}
