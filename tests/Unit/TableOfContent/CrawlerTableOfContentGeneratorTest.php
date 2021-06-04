<?php

/*
 * This file is part of the "StenopePHP/Stenope" bundle.
 *
 * @author Thomas Jarrand <thomas.jarrand@gmail.com>
 */

namespace Stenope\Bundle\Tests\Unit\Service;

use PHPUnit\Framework\TestCase;
use Stenope\Bundle\TableOfContent\CrawlerTableOfContentGenerator;
use Stenope\Bundle\TableOfContent\Headline;
use Symfony\Component\DomCrawler\Crawler;

class CrawlerTableOfContentGeneratorTest extends TestCase
{
    private CrawlerTableOfContentGenerator $generator;

    protected function setUp(): void
    {
        $this->generator = new CrawlerTableOfContentGenerator();
    }

    /**
     * @dataProvider provideTableOfContents
     */
    public function testTableOfContent(string $htmlDom, ?int $fromDepth, ?int $toDepth, array $expectedToc): void
    {
        $toc = $this->generator->getTableOfContent(new Crawler($htmlDom), $fromDepth, $toDepth);

        self::assertJsonStringEqualsJsonString(json_encode($expectedToc), json_encode($toc));
    }

    public function provideTableOfContents(): iterable
    {
        $content = <<<HTML
        <div class="admonition note">
            <h1 id="A">Lorem ipsum</h1>
                <h2 id="AA">Suspendisse</h2>
                    <h3 id="AAA">Dolor</h3>
                    <h3 id="AAB">Sit amet</h3>
                        <h4 id="AABA">Consectetur</h4>
                            <h5 id="AABAA">Nulla faucibus</h5>
                            <h5 id="AABAB">Vestibulum</h5>
                                <h6 id="AABABA">Tincidunt</h6>
                    <h3 id="AAC">Magna non rhoncus</h3>
                        <h4 id="AACA">Id sapien</h4>
                    <h3 id="AAD">Sit amet</h3>
                <h2 id="AB">Nam sed neque</h2>
                <h2 id="AC">Donec laoreet</h2>
                    <!-- Intentionally "jumoing" one level here -->
                    <h4 id="ACA">Himenaeos</h4>
                        <h5 id="ACAA">Suscipit</h5>
                        <h5 id="ACAB">Pretium</h5>
        </div>
        HTML;

        yield 'basic' => [
            $content,
            null,
            null,
            [
                new Headline(1, 'A', 'Lorem ipsum', [
                    new Headline(2, 'AA', 'Suspendisse', [
                        new Headline(3, 'AAA', 'Dolor'),
                        new Headline(3, 'AAB', 'Sit amet', [
                            new Headline(4, 'AABA', 'Consectetur', [
                                new Headline(5, 'AABAA', 'Nulla faucibus'),
                                new Headline(5, 'AABAB', 'Vestibulum', [
                                    new Headline(6, 'AABABA', 'Tincidunt'),
                                ]),
                            ]),
                        ]),
                        new Headline(3, 'AAC', 'Magna non rhoncus', [
                            new Headline(4, 'AACA', 'Id sapien'),
                        ]),
                        new Headline(3, 'AAD', 'Sit amet'),
                    ]),
                    new Headline(2, 'AB', 'Nam sed neque'),
                    new Headline(2, 'AC', 'Donec laoreet', [
                        new Headline(4, 'ACA', 'Himenaeos', [
                            new Headline(5, 'ACAA', 'Suscipit'),
                            new Headline(5, 'ACAB', 'Pretium'),
                        ]),
                    ]),
                ]),
            ],
        ];

        yield 'subset' => [
            $content,
            2,
            3,
            [
                new Headline(2, 'AA', 'Suspendisse', [
                    new Headline(3, 'AAA', 'Dolor'),
                    new Headline(3, 'AAB', 'Sit amet'),
                    new Headline(3, 'AAC', 'Magna non rhoncus'),
                    new Headline(3, 'AAD', 'Sit amet'),
                ]),
                new Headline(2, 'AB', 'Nam sed neque'),
                new Headline(2, 'AC', 'Donec laoreet'),
            ],
        ];

        yield 'single level' => [
            $content,
            2,
            2,
            [
                new Headline(2, 'AA', 'Suspendisse'),
                new Headline(2, 'AB', 'Nam sed neque'),
                new Headline(2, 'AC', 'Donec laoreet'),
            ],
        ];
    }
}
