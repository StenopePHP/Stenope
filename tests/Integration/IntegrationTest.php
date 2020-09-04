<?php

/*
 * This file is part of the "Tom32i/Content" bundle.
 *
 * @author Thomas Jarrand <thomas.jarrand@gmail.com>
 */

namespace Content\Tests\Integration;

use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\ApplicationTester;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\DomCrawler\Link;
use Symfony\Component\Filesystem\Filesystem;

class IntegrationTest extends KernelTestCase
{
    public static array $kernelOptions = [
        'environment' => 'prod',
        'debug' => true,
    ];

    protected function setUp(): void
    {
        static::bootKernel(self::$kernelOptions);
    }

    /**
     * This test is a dependency of all other tests,
     * so failing will skip the next tests.
     */
    public function testBuildApp(): void
    {
        // Empty build & cache
        ($fs = new Filesystem())->remove(($kernel = self::createKernel(self::$kernelOptions))->getCacheDir());
        $fs->remove($kernel->getProjectDir() . '/build');

        $kernel = static::bootKernel(self::$kernelOptions);

        $application = new Application($kernel);
        $application->setAutoExit(false);

        $tester = new ApplicationTester($application);
        $tester->run(['content:build', '--ansi']);

        self::assertSame(Command::SUCCESS, $tester->getStatusCode(), <<<TXT
        The site cannot be build properly.
        Inspect output below:
        ---
        {$tester->getDisplay(true)}
        TXT
        );
    }

    /**
     * @depends testBuildApp
     */
    public function testBuildDirIsCreated(): void
    {
        self::assertDirectoryExists(self::$kernel->getProjectDir() . '/build');
    }

    /**
     * @depends testBuildApp
     */
    public function testCopiedFiles(): void
    {
        self::assertDirectoryExists(self::$kernel->getProjectDir() . '/build/dist');
        self::assertFileExists(self::$kernel->getProjectDir() . '/build/dist/app.css');
        self::assertFileExists(self::$kernel->getProjectDir() . '/build/robots.txt');
    }

    /**
     * @depends testBuildApp
     */
    public function testSiteMap(): void
    {
        self::assertFileExists(self::$kernel->getProjectDir() . '/build/sitemap.xml');

        $crawler = new Crawler(file_get_contents(self::$kernel->getProjectDir() . '/build/sitemap.xml'));

        self::assertSame([
            'http://localhost/',
            'http://localhost/recipes/',
            'http://localhost/recipes/cheesecake',
            'http://localhost/recipes/ogito',
        ], $crawler->filter('url > loc')->extract(['_text']));
    }

    /**
     * @depends testBuildApp
     */
    public function testHomepage(): void
    {
        self::assertFileExists(self::$kernel->getProjectDir() . '/build/index.html');
    }

    /**
     * @depends testBuildApp
     */
    public function testRecipes(): void
    {
        $buildDir = self::$kernel->getProjectDir() . '/build';
        self::assertDirectoryExists($buildDir . '/recipes');
        self::assertFileExists($buildDir . '/recipes/index.html');
        self::assertFileExists($buildDir . '/recipes/cheesecake/index.html');
        self::assertFileExists($buildDir . '/recipes/ogito/index.html');

        $crawler = new Crawler(file_get_contents($buildDir . '/recipes/index.html'), 'http://localhost/recipes/');
        $links = array_map(fn (Link $link) => $link->getUri(), $crawler->filter('main .container a')->links());

        self::assertSame([
            'http://localhost/recipes/cheesecake',
            'http://localhost/recipes/ogito',
        ], $links, 'all recipes links generated in right order');
    }
}
