<?php

/*
 * This file is part of the "StenopePHP/Stenope" bundle.
 *
 * @author Thomas Jarrand <thomas.jarrand@gmail.com>
 */

namespace Stenope\Bundle;

use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Stenope\Bundle\Builder\PageList;
use Stenope\Bundle\Builder\Sitemap;
use Stenope\Bundle\Exception\ContentNotFoundException;
use Stenope\Bundle\HttpFoundation\ContentRequest;
use Stenope\Bundle\Routing\RouteInfoCollection;
use Symfony\Component\Console\Helper\Helper;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\Glob;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\Mime\MimeTypesInterface;
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
    private RouteInfoCollection $routesInfo;
    private HttpKernelInterface $httpKernel;
    private Environment $templating;
    private PageList $pageList;
    private Sitemap $sitemap;

    /** Path to output the static site */
    private string $buildDir;
    private FileSystem $files;
    private MimeTypesInterface $mimeTypes;

    /** Files to copy after build */
    private array $filesToCopy;
    private LoggerInterface $logger;
    private Stopwatch $stopwatch;

    public function __construct(
        RouterInterface $router,
        RouteInfoCollection $routesInfo,
        HttpKernelInterface $httpKernel,
        Environment $templating,
        MimeTypesInterface $mimeTypes,
        PageList $pageList,
        Sitemap $sitemap,
        string $buildDir,
        array $filesToCopy = [],
        ?LoggerInterface $logger = null,
        ?Stopwatch $stopwatch = null
    ) {
        $this->router = $router;
        $this->routesInfo = $routesInfo;
        $this->httpKernel = $httpKernel;
        $this->templating = $templating;
        $this->mimeTypes = $mimeTypes;
        $this->pageList = $pageList;
        $this->sitemap = $sitemap;
        $this->buildDir = $buildDir;
        $this->filesToCopy = $filesToCopy;
        $this->files = new Filesystem();
        $this->logger = $logger ?? new NullLogger();
        $this->stopwatch = $stopwatch ?? new Stopwatch(true);
    }

    public function iterate(
        bool $sitemap = true,
        bool $expose = true,
        bool $ignoreContentNotFoundErrors = false
    ): \Generator {
        return yield from $this->doBuild($sitemap, $expose, $ignoreContentNotFoundErrors);
    }

    /**
     * Build static site
     *
     * @return int Number of pages built
     */
    public function build(bool $sitemap = true, bool $expose = true, bool $ignoreContentNotFoundErrors = false): int
    {
        iterator_to_array($generator = $this->doBuild($sitemap, $expose, $ignoreContentNotFoundErrors));

        return $generator->getReturn();
    }

    /**
     * Build static site
     */
    private function doBuild(
        bool $sitemap = true,
        bool $expose = true,
        bool $ignoreContentNotFoundErrors = false
    ): \Generator {
        yield 'start' => $this->notifyContext('Start building');

        if (!$this->stopwatch->isStarted('build')) {
            $this->stopwatch->start('build', 'stenope');
        }

        yield 'clear' => $this->notifyContext('Clearing previous build');

        $this->clear();

        yield 'scan' => $this->notifyContext('Scanning routes');

        $this->scanAllRoutes();

        yield 'build_pages' => $this->notifyContext('Building pages...');

        $pagesCount = 0;
        yield from $this->buildPages($pagesCount, $ignoreContentNotFoundErrors);

        if ($expose) {
            yield 'copy' => $this->notifyContext('Copying files');
            $this->copyFiles();
        }

        if ($sitemap) {
            yield 'build_sitemap' => $this->notifyContext('Building sitemap...');
            $this->buildSitemap();
        }

        if ($this->stopwatch->isStarted('build')) {
            $this->stopwatch->stop('build');
        }

        yield 'end' => $this->notifyContext();

        return $pagesCount;
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

    public function setBaseUrl(string $baseUrl): void
    {
        $this->router->getContext()->setBaseUrl('/' . ltrim($baseUrl, '/'));
    }

    public function getBaseUrl(): string
    {
        return $this->router->getContext()->getBaseUrl();
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

        $routes = $this->routesInfo;

        $this->logger->notice('Scanning {count} routes...', ['count' => \count($routes)]);

        $skipped = 0;
        foreach ($routes as $name => $route) {
            if ($route->isIgnored() || !$route->isGettable()) {
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
     * @param int Number of pages built
     */
    private function buildPages(int &$pagesCount, bool $ignoreContentNotFoundErrors = false): iterable
    {
        $this->stopwatch->openSection();
        $this->stopwatch->start('build_pages');

        $this->logger->notice('Building pages...', ['entrypoints' => $this->pageList->count()]);

        while ($url = $this->pageList->getNext()) {
            yield $this->notifyContext("Building $url", 1, \count($this->pageList));

            $this->buildUrl($url, $ignoreContentNotFoundErrors);
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

        $pagesCount = \count($this->pageList);
    }

    /**
     * Build xml sitemap file
     */
    private function buildSitemap(): void
    {
        $this->stopwatch->openSection();
        $this->stopwatch->start('build_sitemap');

        $this->logger->notice('Building sitemap...');

        $content = $this->templating->render('@Stenope/sitemap.xml.twig', ['sitemap' => $this->sitemap]);

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
            'ignore_dot_files' => $ignoreDotFiles,
            'excludes' => $excludes,
        ]) {
            $dest ??= basename($src);

            if (is_dir($src)) {
                $this->files->mirror($src, "$this->buildDir/$dest", (new Finder())
                    ->in($src)
                    ->ignoreDotFiles($ignoreDotFiles)
                    ->notPath(array_map(fn ($exclude) => Glob::toRegex($exclude, true, false), $excludes))
                    ->files()
                );

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
    private function buildUrl(string $url, bool $ignoreContentNotFoundErrors = false): void
    {
        $this->logger->debug('Attempt to build page {url}…', ['url' => $url]);

        $request = ContentRequest::create($url, 'GET')->withBaseUrl($this->router->getContext()->getBaseUrl());

        ob_start();

        try {
            $response = $this->httpKernel->handle($request, HttpKernelInterface::MASTER_REQUEST, false);
        } catch (\Throwable $exception) {
            if ($ignoreContentNotFoundErrors && $exception instanceof ContentNotFoundException) {
                $this->logger->warning('Could not build url {url}: {exception}', [
                    'exception' => $exception->getMessage(),
                    'url' => $url,
                ]);

                return;
            }

            throw new \Exception(sprintf('Could not build url %s.', $url), 0, $exception);
        }

        $this->httpKernel->terminate($request, $response);

        if ($response instanceof BinaryFileResponse || $response instanceof StreamedResponse) {
            $response->sendContent();
        }

        $output = ob_get_clean();
        $content = $response->getContent() ?: $output;

        if ($this->hasNoIndexHeader($response) && !$this->hasNoIndexTag($content)) {
            $this->logger->warning('Url {url} contains a "x-robots-tag: noindex" header that will be lost by going static. Consider using the HTML tag "<meta name="robots" content="noindex" />" instead.', [
                'url' => $url,
            ]);
        }

        [$path, $file] = $this->getFilePath($request, $response);

        $this->write($content, $path, $file);

        $periods = $this->stopwatch->lap('build_pages')->getPeriods();
        $period = end($periods);
        $time = $period->getDuration();
        $memory = $period->getMemory();

        $this->logger->debug('Page {url} built ({time}, {memory})', [
            'time' => self::formatTime($time),
            'memory' => self::formatMemory($memory),
            'url' => $url,
        ]);
    }

    /**
     * Test the presence of a "NoIndex" HTTP Header.
     */
    private function hasNoIndexHeader(Response $response): bool
    {
        return \in_array('noindex', $response->headers->all('x-robots-tag'));
    }

    /**
     * Test the presence of a "NoIndex" meta tag.
     */
    private function hasNoIndexTag(string $content): bool
    {
        $crawler = new Crawler($content);

        return $crawler->filter('head > meta[content="noindex"]')->count() > 0;
    }

    /**
     * Get file path from URL
     */
    private function getFilePath(Request $request, Response $response): array
    {
        $url = rawurldecode($request->getPathInfo());
        $info = pathinfo($url);
        $extension = $info['extension'] ?? null;

        // If the request has html format, but the .html extension is not already part of the url
        if ('html' !== $extension && $this->isHtml($request, $response)) {
            // we must generate an index.html file
            return [$url, 'index.html'];
        }

        // otherwise, dump as is:
        return [$info['dirname'], $info['basename']];
    }

    private function isHtml(Request $request, Response $response): bool
    {
        if ('html' === $request->getRequestFormat(null)) {
            return true;
        }

        $contentType = explode(';', $response->headers->get('Content-Type', 'text/html'))[0];

        return \in_array('html', $this->mimeTypes->getExtensions($contentType));
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

    private function notifyContext(
        ?string $message = null,
        ?int $advance = null,
        ?int $maxStep = null
    ): array {
        return [
            'advance' => $advance,
            'maxStep' => $maxStep,
            'message' => $message,
        ];
    }
}
