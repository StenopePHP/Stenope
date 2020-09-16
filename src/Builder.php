<?php

/*
 * This file is part of the "Tom32i/Content" bundle.
 *
 * @author Thomas Jarrand <thomas.jarrand@gmail.com>
 */

namespace Content;

use Content\Builder\BuildNotifierInterface;
use Content\Builder\PageList;
use Content\Builder\RouteInfo;
use Content\Builder\Sitemap;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Symfony\Component\Console\Helper\Helper;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\Glob;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\Routing\Exception\MissingMandatoryParametersException;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Stopwatch\Stopwatch;
use Twig\Environment;

/**
 * Static route builder
 */
class Builder
{
    private RouterInterface $router;
    private HttpKernelInterface $httpKernel;
    private Environment $templating;
    private PageList $pageList;
    private Sitemap $sitemap;

    /** Path to output the static site */
    private string $buildDir;
    private FileSystem $files;

    /** Files to copy after build */
    private array $filesToCopy;
    private LoggerInterface $logger;
    private Stopwatch $stopwatch;

    public function __construct(
        RouterInterface $router,
        HttpKernelInterface $httpKernel,
        Environment $templating,
        PageList $pageList,
        Sitemap $sitemap,
        string $buildDir,
        array $filesToCopy = [],
        ?LoggerInterface $logger = null,
        ?Stopwatch $stopwatch = null
    ) {
        $this->router = $router;
        $this->httpKernel = $httpKernel;
        $this->templating = $templating;
        $this->pageList = $pageList;
        $this->sitemap = $sitemap;
        $this->buildDir = $buildDir;
        $this->filesToCopy = $filesToCopy;
        $this->files = new Filesystem();
        $this->logger = $logger ?? new NullLogger();
        $this->stopwatch = $stopwatch ?? new Stopwatch(true);
    }

    /**
     * Build static site
     *
     * @return int Number of pages built
     */
    public function build(bool $sitemap = true, bool $expose = true, ?BuildNotifierInterface $notifier = null): int
    {
        $notifier && $notifier->notify('start', null, null, 'Start building');

        if (!$this->stopwatch->isStarted('build')) {
            $this->stopwatch->start('build', 'content');
        }

        $notifier && $notifier->notify('clear', null, null, 'Clearing previous build');

        $this->clear();

        $notifier && $notifier->notify('scan', null, null, 'Scanning routes');

        $this->scanAllRoutes();

        if ($expose) {
            $notifier && $notifier->notify('copy', null, null, 'Copying files');
            $this->copyFiles();
        }

        $notifier && $notifier->notify('build_pages', null, null, 'Building pages...');

        $pages = $this->buildPages($notifier);

        if ($sitemap) {
            $notifier && $notifier->notify('build_sitemap', null, null, 'Building sitemap...');
            $this->buildSitemap();
        }

        if ($this->stopwatch->isStarted('build')) {
            $this->stopwatch->stop('build');
        }

        $notifier && $notifier->notify('end');

        return $pages;
    }

    public function setBuildDir(string $buildDir): void
    {
        $this->buildDir = $buildDir;
    }

    public function getBuildDir(): string
    {
        return $this->buildDir;
    }

    /**
     * Set host name
     */
    public function setHost(string $host): void
    {
        $this->router->getContext()->setHost($host);
    }

    public function getHost(): string
    {
        return $this->router->getContext()->getHost();
    }

    /**
     * Set HTTP Scheme
     */
    public function setScheme(string $scheme): void
    {
        $this->router->getContext()->setScheme($scheme);
    }

    public function getScheme(): string
    {
        return $this->router->getContext()->getScheme();
    }

    /**
     * Clear destination folder
     */
    private function clear(): void
    {
        $this->stopwatch->openSection();
        $this->stopwatch->start('clear');

        $this->logger->notice('Clearing {build_dir} build directory...', ['build_dir' => $this->buildDir]);

        if ($this->files->exists($this->buildDir)) {
            $this->files->remove($this->buildDir);
        }

        $this->files->mkdir($this->buildDir);

        $time = $this->stopwatch->lap('clear')->getDuration();
        $this->stopwatch->stopSection('clear');

        $this->logger->info('Cleared {build_dir} build directory! ({time})', [
            'build_dir' => $this->buildDir,
            'time' => self::formatTime($time),
        ]);
    }

    /**
     * Scan all declared route and tries to add them to the page list.
     */
    private function scanAllRoutes(): void
    {
        $this->stopwatch->openSection();
        $this->stopwatch->start('scan_routes');

        $routes = RouteInfo::createFromRouteCollection($this->router->getRouteCollection());

        $this->logger->notice('Scanning {count} routes...', ['count' => \count($routes)]);

        $skipped = 0;
        foreach ($routes as $name => $route) {
            if (!$route->isVisible() || !$route->isGettable()) {
                $this->logger->debug('Route "{route}" is hidden, skipping.', ['route' => $name]);
                continue;
            }

            try {
                $url = $this->router->generate($name, [], UrlGeneratorInterface::ABSOLUTE_URL);
            } catch (MissingMandatoryParametersException $exception) {
                ++$skipped;
                $this->logger->debug('Route "{route}" requires parameters, skipping.', ['route' => $name]);
                continue;
            }

            $this->pageList->add($url);
            $this->logger->debug('Route "{route}" is successfully listed.', ['route' => $name]);
        }

        $lap = $this->stopwatch->lap('scan_routes');
        $time = $lap->getDuration();
        $memory = $lap->getMemory();
        $this->stopwatch->stopSection('scan_routes');

        $this->logger->info('Scanned {scanned} routes ({skipped} skipped), discovered {count} entrypoint routes! ({time}, {memory})', [
            'time' => self::formatTime($time),
            'scanned' => \count($routes),
            'skipped' => $skipped,
            'count' => \count($this->pageList),
            'memory' => self::formatMemory($memory),
        ]);
    }

    /**
     * Build all pages
     *
     * @return int Number of pages built
     */
    private function buildPages(?BuildNotifierInterface $notifier = null): int
    {
        $this->stopwatch->openSection();
        $this->stopwatch->start('build_pages');

        $this->logger->notice('Building pages...', ['entrypoints' => $this->pageList->count()]);

        while ($url = $this->pageList->getNext()) {
            $notifier && $notifier->notify(null, 1, \count($this->pageList), "Building $url");

            $this->buildUrl($url);
            $this->pageList->markAsDone($url);
        }

        $memory = $this->stopwatch->lap('build_pages')->getMemory();
        $this->stopwatch->stopSection('build_pages');
        $events = $this->stopwatch->getSectionEvents('build_pages');

        $this->logger->info('Built {count} pages! ({time}, {memory})', [
            'time' => self::formatTime(end($events)->getDuration()),
            'memory' => self::formatMemory($memory),
            'count' => \count($this->pageList),
        ]);

        return \count($this->pageList);
    }

    /**
     * Build xml sitemap file
     */
    private function buildSitemap(): void
    {
        $this->stopwatch->openSection();
        $this->stopwatch->start('build_sitemap');

        $this->logger->notice('Building sitemap...');

        $content = $this->templating->render('@Content/sitemap.xml.twig', ['sitemap' => $this->sitemap]);

        $this->write($content, '/', 'sitemap.xml');

        $lap = $this->stopwatch->lap('build_sitemap');
        $this->stopwatch->stopSection('build_sitemap');

        $this->logger->info('Built sitemap! ({time}, {memory})', [
            'time' => self::formatTime($lap->getDuration()),
            'memory' => self::formatMemory($lap->getMemory()),
        ]);
    }

    private function copyFiles(): void
    {
        foreach ($this->filesToCopy as [
            'src' => $src,
            'dest' => $dest,
            'fail_if_missing' => $failIfMissing,
            'excludes' => $excludes,
        ]) {
            $dest ??= basename($src);

            if (is_dir($src)) {
                if (\count($excludes) > 0) {
                    $iterator = (new Finder())
                        ->in($src)
                        ->files()
                        ->notPath(array_map(fn ($exclude) => Glob::toRegex($exclude, true, false), $excludes))
                    ;
                }

                $this->files->mirror($src, "$this->buildDir/$dest", $iterator ?? null);

                continue;
            }

            if (!is_file($src)) {
                if ($failIfMissing) {
                    throw new \RuntimeException(sprintf(
                        'Failed to copy "%s" because the path is neither a file or a directory.',
                        $src
                    ));
                }

                $this->logger->warning('Failed to copy "{src}" because the path is neither a file or a directory.', [
                    'src' => $src,
                    'dest' => $dest,
                ]);

                continue;
            }

            $this->files->copy($src, "$this->buildDir/$dest");
        }
    }

    /**
     * Build the given Route into a file
     */
    private function buildUrl(string $url): void
    {
        $periods = $this->stopwatch->lap('build_pages')->getPeriods();
        $period = end($periods);
        $time = $period->getDuration();
        $memory = $period->getMemory();

        $request = Request::create($url, 'GET');

        try {
            $response = $this->httpKernel->handle($request, HttpKernelInterface::MASTER_REQUEST, false);
        } catch (\Throwable $exception) {
            throw new \Exception(sprintf('Could not build url "%s".', $url), 0, $exception);
        }

        $this->httpKernel->terminate($request, $response);

        [$path, $file] = $this->getFilePath($request->getPathInfo());

        $this->write($response->getContent(), $path, $file);

        $this->logger->debug('Page "{url}" built ({time}, {memory})', [
            'time' => self::formatTime($time),
            'memory' => self::formatMemory($memory),
            'url' => $url,
        ]);
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
        $directory = sprintf('%s/%s', $this->buildDir, trim($path, '/'));

        if (!$this->files->exists($directory)) {
            $this->files->mkdir($directory);
        }

        $this->files->dumpFile(sprintf('%s/%s', $directory, $file), $content);
    }

    private static function formatTime(float $time): string
    {
        if ($time >= 1000) {
            return number_format($time / 1000, 2) . ' s';
        }

        return number_format($time, 2) . ' ms';
    }

    private static function formatMemory(int $memory): string
    {
        return Helper::formatMemory($memory);
    }
}
