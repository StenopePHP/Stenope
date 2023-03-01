<?php

/*
 * This file is part of the "StenopePHP/Stenope" bundle.
 *
 * @author Thomas Jarrand <thomas.jarrand@gmail.com>
 */

namespace Stenope\Bundle\Tests\Integration;

use Psr\Log\Test\TestLogger;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Tester\ApplicationTester;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\DomCrawler\Link;
use Symfony\Component\Filesystem\Filesystem;

class BuildTest extends KernelTestCase
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
        $tester->run(['stenope:build', '--ansi'], [
            'interactive' => false,
            'verbosity' => ConsoleOutput::VERBOSITY_NORMAL,
        ]);

        $output = $tester->getDisplay(true);

        self::assertSame(Command::SUCCESS, $tester->getStatusCode(), <<<TXT
        The site cannot be build properly.
        Inspect output below:
        ---
        $output
        TXT
        );

        $this->assertStringContainsString('[OK] Built 19 pages.', $output);

        /** @var TestLogger $logger */
        $logger = static::getContainer()->get('logger');

        $logger->hasWarningThatContains('Url "http://localhost/without-noindex" contains a "x-robots-tag: noindex" header that will be lost by going static.');
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
        self::assertDirectoryExists(self::$kernel->getProjectDir() . '/build/build');
        self::assertFileExists(self::$kernel->getProjectDir() . '/build/build/app.css');
        self::assertFileExists(self::$kernel->getProjectDir() . '/build/robots.txt');
        self::assertFileDoesNotExist(self::$kernel->getProjectDir() . '/build/index.php');
    }

    /**
     * @depends testBuildApp
     */
    public function testSiteMap(): void
    {
        self::assertFileExists(self::$kernel->getProjectDir() . '/build/sitemap.xml');

        $crawler = new Crawler(file_get_contents(self::$kernel->getProjectDir() . '/build/sitemap.xml'));

        self::assertEqualsCanonicalizing([
            'http://localhost/',
            'http://localhost/foo.html',
            'http://localhost/authors/john.doe',
            'http://localhost/authors/ogi',
            'http://localhost/authors/tom32i',
            'http://localhost/recipes/',
            'http://localhost/recipes/cheesecake',
            'http://localhost/recipes/ogito',
            'http://localhost/recipes/stockholm%20mule',
            'http://localhost/recipes/tomiritsu',
            'http://localhost/with-noindex',
            'http://localhost/without-noindex',
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
        self::assertFileExists($buildDir . '/recipes/stockholm mule/index.html');
        self::assertFileExists($buildDir . '/recipes/ogito/index.html');

        $crawler = new Crawler(file_get_contents($buildDir . '/recipes/index.html'), 'http://localhost/recipes/');
        $links = array_map(fn (Link $link) => $link->getUri(), $crawler->filter('main .container a.recipe-link')->links());

        self::assertSame([
            'http://localhost/recipes/stockholm%20mule',
            'http://localhost/recipes/cheesecake',
            'http://localhost/recipes/ogito',
            'http://localhost/recipes/tomiritsu',
        ], $links, 'all recipes links generated in right order');
    }

    /**
     * We can have custom controllers rendering content in another format than html, with their own extension in url.
     * For such routes, the Builder won't generate an index.html file as soon as the proper format
     * is provided into the request (through the route `format` option or the request `_format` attribute).
     *
     * @depends testBuildApp
     */
    public function testRecipesAsPdf(): void
    {
        $buildDir = self::$kernel->getProjectDir() . '/build';
        self::assertDirectoryExists($buildDir . '/recipes');

        self::assertFileExists($path = $buildDir . '/recipes/cheesecake.pdf');
        self::assertDirectoryDoesNotExist($path);

        self::assertFileExists($path = $buildDir . '/recipes/ogito.pdf');
        self::assertDirectoryDoesNotExist($path);

        self::assertFileExists($path = $buildDir . '/recipes/stockholm mule.pdf');
        self::assertDirectoryDoesNotExist($path);
    }

    /**
     * @depends testBuildApp
     */
    public function testAuthors(): void
    {
        self::assertFileExists(self::$kernel->getProjectDir() . '/build/authors/ogi/index.html');
        self::assertFileExists(
            self::$kernel->getProjectDir() . '/build/authors/john.doe/index.html',
            'Ensures content with dot in slug generates an index.html file.',
        );
    }

    /**
     * In the same way as {@link self::testRecipesAsPdf},
     * we can expose a micro API as plain JSON files for authors.
     *
     * @depends testBuildApp
     */
    public function testAuthorsAsJson(): void
    {
        self::assertFileExists($path = self::$kernel->getProjectDir() . '/build/authors/ogi.json');
        self::assertDirectoryDoesNotExist($path);

        self::assertFileExists($path = self::$kernel->getProjectDir() . '/build/authors/john.doe.json');
        self::assertDirectoryDoesNotExist($path);
    }

    /**
     * The builder should not generate a dir/index.html for an url already ending with ".html".
     *
     * @depends testBuildApp
     */
    public function testWithHtmlExtensionDoesNotGeneratesDir(): void
    {
        self::assertFileExists($path = self::$kernel->getProjectDir() . '/build/foo.html');
        self::assertDirectoryDoesNotExist($path);
    }
}
