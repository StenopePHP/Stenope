<?php

/*
 * This file is part of the "Tom32i/Content" bundle.
 *
 * @author Thomas Jarrand <thomas.jarrand@gmail.com>
 */

namespace Content;

use Content\Builder\AssetList;
use Content\Builder\PageList;
use Content\Builder\RouteInfo;
use Content\Builder\Sitemap;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouterInterface;
use Symfony\WebpackEncoreBundle\Asset\EntrypointLookupInterface;
use Twig\Environment;

/**
 * Static route builder
 */
class Builder
{
    /**
     * Router
     *
     * @var RouterInterface
     */
    private $router;

    /**
     * HTTP Kernel
     *
     * @var HttpKernelInterface
     */
    private $httpKernel;

    /**
     * Url Generator
     *
     * @var UrlGeneratorInterface
     */
    private $urlGenerator;

    /**
     * Encore webpack
     *
     * @var EntrypointLookupInterface
     */
    private $entrypointLookup;

    /**
     * Twig templating engine
     *
     * @var Environment
     */
    private $templating;

    /**
     * Asset list
     *
     * @var AssetList
     */
    private $assetList;

    /**
     * Page list
     *
     * @var PageList
     */
    private $pageList;

    /**
     * Sitemap
     *
     * @var Sitemap
     */
    private $sitemap;

    /**
     * Path to output the static site
     *
     * @var string
     */
    private $destination;

    /**
     * Public path
     *
     * @var string
     */
    private $public;

    /**
     * File system
     *
     * @var FileSystem
     */
    private $files;

    public function __construct(
        RouterInterface $router,
        HttpKernelInterface $httpKernel,
        UrlGeneratorInterface $urlGenerator,
        EntrypointLookupInterface $entrypointLookup,
        Environment $templating,
        AssetList $assetList,
        PageList $pageList,
        Sitemap $sitemap,
        string $public,
        string $destination
    ) {
        $this->router = $router;
        $this->httpKernel = $httpKernel;
        $this->urlGenerator = $urlGenerator;
        $this->templating = $templating;
        $this->entrypointLookup = $entrypointLookup;
        $this->assetList = $assetList;
        $this->pageList = $pageList;
        $this->sitemap = $sitemap;
        $this->destination = $destination;
        $this->public = $public;
        $this->files = new Filesystem();
    }

    /**
     * Bluid static site
     */
    public function build(bool $sitemap = true, bool $assets = true): void
    {
        $this->clear();

        $this->scanAllRoutes();

        $this->buildPages();

        if ($sitemap) {
            $this->buildSitemap();
        }

        if ($assets) {
            $this->buildAssets();
        }
    }

    /**
     * Set output path
     */
    public function setDestination(string $destination = null): void
    {
        $this->destination = $destination;
    }

    /**
     * Set host name
     */
    public function setHost(string $host): void
    {
        $this->urlGenerator->getContext()->setHost($host);
    }

    /**
     * Set HTTP Scheme
     */
    public function setScheme(string $scheme): void
    {
        $this->urlGenerator->getContext()->setScheme($scheme);
    }

    /**
     * Clear destination folder
     */
    private function clear(): void
    {
        if ($this->files->exists($this->destination)) {
            $this->files->remove($this->destination);
        }

        $this->files->mkdir($this->destination);
    }

    /**
     * Scall all declared route and tries to add them to the page list.
     */
    private function scanAllRoutes(): void
    {
        $routes = RouteInfo::createFromRouteCollection($this->router->getRouteCollection());

        foreach ($routes as $name => $route) {
            if ($route->isVisible() && $route->isGettable()) {
                try {
                    $url = $this->urlGenerator->generate($name, [], UrlGeneratorInterface::ABSOLUTE_URL);
                } catch (\Exception $exception) {
                    continue;
                }

                $this->pageList->add($url);
            }
        }
    }

    /**
     * Build all pages
     */
    private function buildPages(): void
    {
        while ($url = $this->pageList->getNext()) {
            $this->buildUrl($url);
            $this->pageList->markAsDone($url);
        }
    }

    /**
     * Build xml sitemap file
     */
    private function buildSitemap(): void
    {
        $content = $this->templating->render('@Content/sitemap.xml.twig', ['sitemap' => $this->sitemap]);

        $this->write($content, '/', 'sitemap.xml');
    }

    /**
     * Expose public assets
     */
    private function buildAssets(): void
    {
        foreach ($this->assetList as $path) {
            $this->files->copy(
                implode('/', [$this->public, $path]),
                implode('/', [$this->destination, $path]),
                true
            );
        }
    }

    /**
     * Build the given Route into a file
     */
    private function buildUrl(string $url): void
    {
        $request = Request::create($url, 'GET');
        $response = $this->httpKernel->handle($request);

        $this->httpKernel->terminate($request, $response);
        $this->entrypointLookup->reset();

        list($path, $file) = $this->getFilePath($request->getPathInfo());

        $this->write($response->getContent(), $path, $file);
    }

    /**
     * Get file path from URL
     */
    private function getFilePath(string $url): array
    {
        $info = pathinfo($url);

        if (!isset($info['extension'])) {
            return [$url, 'index.html'];
        }

        return [$info['dirname'], $info['basename']];
    }

    /**
     * Write a file
     *
     * @param string $content The file content
     * @param string $path    The directory to put the file in (in the current destination)
     * @param string $file    The file name
     */
    private function write(string $content, string $path, string $file): void
    {
        $directory = sprintf('%s/%s', $this->destination, trim($path, '/'));

        if (!$this->files->exists($directory)) {
            $this->files->mkdir($directory);
        }

        $this->files->dumpFile(sprintf('%s/%s', $directory, $file), $content);
    }
}
