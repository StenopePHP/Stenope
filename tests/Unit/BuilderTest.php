<?php

/*
 * This file is part of the "StenopePHP/Stenope" bundle.
 *
 * @author Thomas Jarrand <thomas.jarrand@gmail.com>
 * @author Maxime Steinhausser <maxime.steinhausser@gmail.com>
 */

namespace Stenope\Bundle\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Stenope\Bundle\Builder;
use Stenope\Bundle\Builder\PageList;
use Stenope\Bundle\Builder\Sitemap;
use Stenope\Bundle\Exception\ContentNotFoundException;
use Stenope\Bundle\Routing\RouteInfoCollection;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\TerminableInterface;
use Symfony\Component\Mime\MimeTypes;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\Routing\RouterInterface;
use Twig\Environment;

class BuilderTest extends TestCase
{
    use ProphecyTrait;

    private string $buildDir;

    /** Output buffering level before the build. */
    private int $obLevel;

    protected function setUp(): void
    {
        $this->buildDir = sys_get_temp_dir() . '/stenope-builder-test';
        $this->obLevel = ob_get_level();
    }

    protected function tearDown(): void
    {
        // Keep a leaked buffer from swallowing the output of the next test.
        while (ob_get_level() > $this->obLevel) {
            ob_end_clean();
        }

        (new Filesystem())->remove($this->buildDir);
    }

    public function testBuildsPageFromEchoedOutput(): void
    {
        $httpKernel = $this->prophesize(HttpKernelInterface::class)->willImplement(TerminableInterface::class);
        $httpKernel->handle(Argument::cetera())->will(function (): Response {
            self::echoes('echoed output');

            return new Response();
        });
        $httpKernel->terminate(Argument::cetera())->shouldBeCalled();

        $this->createBuilder($httpKernel->reveal(), 'http://localhost/')
            ->build(
                sitemap: false,
                expose: false,
            );

        self::assertStringEqualsFile("$this->buildDir/index.html", 'echoed output');
    }

    public function testIgnoredContentNotFoundClosesOutputBuffer(): void
    {
        $httpKernel = $this->prophesize(HttpKernelInterface::class);
        $httpKernel->handle(Argument::cetera())->will(function (): Response {
            self::echoes('partially rendered page');

            throw new ContentNotFoundException('App\Recipe', 'missing');
        });

        $this->createBuilder($httpKernel->reveal(), 'http://localhost/missing')
            ->build(
                sitemap: false,
                expose: false,
                ignoreContentNotFoundErrors: true,
            );

        self::assertSame(
            expected: $this->obLevel,
            actual: ob_get_level(),
            message: 'Builder::buildUrl() must close its output buffer when a page is ignored.',
        );
    }

    public function testClosesBuffersLeftOpenByAnInterruptedPage(): void
    {
        $httpKernel = $this->prophesize(HttpKernelInterface::class);
        $httpKernel->handle(Argument::cetera())->will(function (): Response {
            // A Twig "apply" block, or any controller buffering on its own, dies mid-rendering.
            ob_start();

            throw new ContentNotFoundException('App\Recipe', 'missing');
        });

        $this->createBuilder($httpKernel->reveal(), 'http://localhost/missing')
            ->build(
                sitemap: false,
                expose: false,
                ignoreContentNotFoundErrors: true,
            );

        self::assertSame(
            expected: $this->obLevel,
            actual: ob_get_level(),
            message: 'Builder::buildUrl() must close the buffers an interrupted page left behind.',
        );
    }

    public function testFailedBuildClosesOutputBuffer(): void
    {
        $httpKernel = $this->prophesize(HttpKernelInterface::class);
        $httpKernel->handle(Argument::cetera())->will(function (): Response {
            self::echoes('partially rendered page');

            throw new \RuntimeException('Kernel exploded');
        });

        $this->expectExceptionMessage('Could not build url http://localhost/broken.');

        try {
            $this->createBuilder($httpKernel->reveal(), 'http://localhost/broken')
                ->build(
                    sitemap: false,
                    expose: false,
                );
        } finally {
            self::assertSame(
                expected: $this->obLevel,
                actual: ob_get_level(),
                message: 'Builder::buildUrl() must close its output buffer when a page fails to build.',
            );
        }
    }

    /**
     * Writes to the builder output buffer, the way a controller echoing its template would.
     *
     * Goes through php://output because the PHPStan banned_code extension rejects "echo".
     */
    private static function echoes(string $output): void
    {
        file_put_contents('php://output', $output);
    }

    private function createBuilder(HttpKernelInterface $httpKernel, string $url): Builder
    {
        $router = $this->prophesize(RouterInterface::class);
        $router->getContext()->willReturn(new RequestContext());
        $router->getRouteCollection()->willReturn(new RouteCollection());

        $pageList = new PageList();
        $pageList->add($url);

        return new Builder(
            router: $router->reveal(),
            routesInfo: new RouteInfoCollection($router->reveal()),
            httpKernel: $httpKernel,
            templating: $this->prophesize(Environment::class)->reveal(),
            mimeTypes: new MimeTypes(),
            pageList: $pageList,
            sitemap: new Sitemap(),
            buildDir: $this->buildDir,
        );
    }
}
