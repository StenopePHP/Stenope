<?php

/*
 * This file is part of the "StenopePHP/Stenope" bundle.
 *
 * @author Thomas Jarrand <thomas.jarrand@gmail.com>
 */

namespace Stenope\Bundle\Routing;

use Stenope\Bundle\Builder\PageList;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RequestContext;

/**
 * A wrapper for UrlGenerator that register every generated url in the PageList.
 *
 * @final
 */
class UrlGenerator implements UrlGeneratorInterface
{
    private UrlGeneratorInterface $urlGenerator;
    private PageList $pageList;
    private RouteInfoCollection $routesInfo;

    public function __construct(RouteInfoCollection $routesInfo, UrlGeneratorInterface $urlGenerator, PageList $pageList)
    {
        $this->urlGenerator = $urlGenerator;
        $this->pageList = $pageList;
        $this->routesInfo = $routesInfo;
    }

    public function generate($name, $parameters = [], $referenceType = UrlGeneratorInterface::ABSOLUTE_PATH): string
    {
        if (($routeInfo = $this->routesInfo[$name] ?? null) && !$routeInfo->isIgnored()) {
            $this->pageList->add(
                $this->urlGenerator->generate($name, $parameters, UrlGeneratorInterface::ABSOLUTE_URL)
            );
        }

        return $this->urlGenerator->generate($name, $parameters, $referenceType);
    }

    public function setContext(RequestContext $context): void
    {
        $this->urlGenerator->setContext($context);
    }

    public function getContext(): RequestContext
    {
        return $this->urlGenerator->getContext();
    }
}
