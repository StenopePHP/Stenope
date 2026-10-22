<?php

/*
 * This file is part of the "StenopePHP/Stenope" bundle.
 *
 * @author Thomas Jarrand <thomas.jarrand@gmail.com>
 * @author Maxime Steinhausser <maxime.steinhausser@gmail.com>
 */

namespace Stenope\Bundle\Tests\Integration;

use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Stenope\Bundle\StenopeBundle;
use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Bundle\TwigBundle\TwigBundle;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpKernel\Kernel;
use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;

/**
 * Stenope prepends the pool configuration instead of defining the service itself, so that an app
 * declaring the same pool wins. Only a container holding both extensions can tell the difference.
 */
class ContentCachePoolTest extends TestCase
{
    /** @var list<string> */
    private array $projectDirs = [];

    protected function tearDown(): void
    {
        (new Filesystem())->remove($this->projectDirs);
    }

    public function testDefaultsToTheSystemCache(): void
    {
        $container = $this->buildContainer();

        self::assertSame(
            expected: $container->get('cache.system')::class,
            actual: $container->get('test.content_cache')::class,
            message: 'Without any app configuration, the content cache must use the system cache adapter.',
        );
    }

    public function testTheAppCanReconfigureIt(): void
    {
        $container = $this->buildContainer(['stenope.content_cache' => ['adapter' => 'cache.adapter.array']]);

        self::assertInstanceOf(
            expected: ArrayAdapter::class,
            actual: $container->get('test.content_cache'),
            message: 'Declaring stenope.content_cache under framework.cache.pools must replace the adapter Stenope prepends.',
        );
    }

    /** @param array<string,mixed> $pools framework.cache.pools, as an app would declare them */
    private function buildContainer(array $pools = []): ContainerInterface
    {
        $kernel = new ContentCacheKernel($this->getName(), $pools);
        $this->projectDirs[] = $kernel->getProjectDir();
        $kernel->boot();

        return $kernel->getContainer();
    }
}

/**
 * @final
 */
class ContentCacheKernel extends Kernel
{
    use MicroKernelTrait;

    /** @param array<string,mixed> $pools */
    public function __construct(private string $id, private array $pools)
    {
        parent::__construct('test', true);
    }

    public function registerBundles(): iterable
    {
        // TwigBundle: Stenope's TwigExtensionFixerCompilerPass looks up twig.extension.routing.
        return [new FrameworkBundle(), new TwigBundle(), new StenopeBundle()];
    }

    public function getProjectDir(): string
    {
        // Per process and per test: parallel runs must not share, and so delete, each other's container.
        return sprintf('%s/stenope-content-cache-%d-%s', sys_get_temp_dir(), getmypid(), $this->id);
    }

    protected function configureContainer(ContainerConfigurator $container): void
    {
        $container->extension('framework', [
            'secret' => 'stenope',
            'http_method_override' => false,
            'cache' => ['pools' => $this->pools],
        ]);
        $container->extension('twig', ['default_path' => $this->getProjectDir()]);

        // The pool is private, as Symfony's own system pools are:
        $container->services()->alias('test.content_cache', 'stenope.content_cache')->public();
    }

    protected function configureRoutes(RoutingConfigurator $routes): void
    {
        // No routes: this kernel only exists to compile a container.
    }
}
