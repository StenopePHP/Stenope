<?php

/*
 * This file is part of the "Tom32i/Content" bundle.
 *
 * @author Thomas Jarrand <thomas.jarrand@gmail.com>
 */

namespace Content\EventListener;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Twig\Environment;

/**
 * Informator
 */
class Informator implements EventSubscriberInterface
{
    /**
     * Url Generator
     *
     * @var UrlGeneratorInterface
     */
    private $urlGenerator;

    /**
     * Twig rendering engine
     *
     * @var Environment
     */
    private $twig;

    /**
     * Injecting dependencies
     */
    public function __construct(UrlGeneratorInterface $urlGenerator, Environment $twig)
    {
        $this->urlGenerator = $urlGenerator;
        $this->twig = $twig;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [KernelEvents::REQUEST => 'onRequest'];
    }

    /**
     * Before request
     */
    public function onRequest(RequestEvent $event): void
    {
        $request = $event->getRequest();

        if ($canonical = $this->getCanonicalUrl($request)) {
            $request->attributes->set('_canonical', $canonical);
            $this->twig->addGlobal('canonical', $canonical);
        }

        if ($root = $this->getRootUrl($request)) {
            $request->attributes->set('_root', $root);
            $this->twig->addGlobal('root', $root);
        }
    }

    /**
     * Get canonical URL
     *
     * @return string
     */
    private function getCanonicalUrl(Request $request)
    {
        if (!$request->attributes->get('_route')) {
            return '';
        }

        return $this->urlGenerator->generate(
            $request->attributes->get('_route'),
            $request->attributes->get('_route_params'),
            UrlGeneratorInterface::ABSOLUTE_URL
        );
    }

    /**
     * Get root URL
     *
     * @return string
     */
    private function getRootUrl(Request $request)
    {
        return sprintf('%s://%s', $request->getScheme(), $request->getHost());
    }
}
