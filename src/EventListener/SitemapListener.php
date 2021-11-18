<?php

/*
 * This file is part of the "StenopePHP/Stenope" bundle.
 *
 * @author Thomas Jarrand <thomas.jarrand@gmail.com>
 */

namespace Stenope\Bundle\EventListener;

use Stenope\Bundle\Builder\Sitemap;
use Stenope\Bundle\Routing\RouteInfoCollection;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Map all routes into a Sitemap
 *
 * @final
 */
class SitemapListener implements EventSubscriberInterface
{
    private RouteInfoCollection $routesInfo;
    private Sitemap $sitemap;

    public function __construct(RouteInfoCollection $routesInfo, Sitemap $sitemap)
    {
        $this->routesInfo = $routesInfo;
        $this->sitemap = $sitemap;
    }

    public function onKernelResponse(ResponseEvent $event): void
    {
        $request = $event->getRequest();
        $response = $event->getResponse();

        if (!$routeName = $request->attributes->get('_route')) {
            return;
        }

        $route = $this->routesInfo[$routeName];

        if ($route && $route->isMapped() && $request->attributes->get('_canonical')) {
            $this->sitemap->add(
                $request->attributes->get('_canonical'),
                new \DateTime($response->headers->get('Last-Modified') ?? 'now')
            );
        }
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents(): array
    {
        return [KernelEvents::RESPONSE => 'onKernelResponse'];
    }
}
