<?php

/*
 * This file is part of the "StenopePHP/Stenope" bundle.
 *
 * @author Thomas Jarrand <thomas.jarrand@gmail.com>
 * @author Maxime Steinhausser <maxime.steinhausser@gmail.com>
 */

namespace Stenope\Bundle\Tests\Unit\Service;

use PHPUnit\Framework\TestCase;
use Stenope\Bundle\Behaviour\HtmlCrawlerManagerInterface;
use Stenope\Bundle\Content;
use Stenope\Bundle\Service\NaiveHtmlCrawlerManager;
use Stenope\Bundle\Service\SharedHtmlCrawlerManager;

class HtmlCrawlerManagerTest extends TestCase
{
    /**
     * @dataProvider provideNoExtraBodyData
     */
    public function testSaveNoExtraBody(
        HtmlCrawlerManagerInterface $manager,
        string $html,
        string $expected
    ): void {
        if ($manager instanceof SharedHtmlCrawlerManager) {
            $this->markTestSkipped('SharedHtmlCrawlerManager does nothing on save()');
        }

        $content = new Content('slug', 'type', $html, 'html');
        $data = ['content' => $html];

        $manager->get($content, $data, 'content');
        $manager->save($content, $data, 'content');

        self::assertXmlStringEqualsXmlString(<<<HTML
            <html>$expected</html>
            HTML,
            <<<HTML
            <html>{$data['content']}</html>
            HTML,
        );
    }

    /**
     * @dataProvider provideNoExtraBodyData
     */
    public function testSaveAllNoExtraBody(
        HtmlCrawlerManagerInterface $manager,
        string $html,
        string $expected
    ): void {
        $content = new Content('slug', 'type', $html, 'html');
        $data = ['content' => $html];

        $manager->get($content, $data, 'content');
        $manager->saveAll($content, $data);

        self::assertXmlStringEqualsXmlString(<<<HTML
            <html>$expected</html>
            HTML,
            <<<HTML
            <html>{$data['content']}</html>
            HTML,
        );
    }

    public function provideNoExtraBodyData(): iterable
    {
        $html = <<<HTML
            <html>
                <head>
                    <title>My title</title>
                </head>
                <body>
                    <h1>My title</h1>
                    <p>My content</p>
                </body>
            </html>
        HTML;

        $expected = <<<HTML
            <h1>My title</h1>
            <p>My content</p>
        HTML;

        yield 'with full HTML and naive manager' => [
            new NaiveHtmlCrawlerManager(),
            $html,
            $expected,
        ];

        yield 'with full HTML and shared manager' => [
            new SharedHtmlCrawlerManager(),
            $html,
            $expected,
        ];

        $html = <<<HTML
            <h1>My title</h1>
            <p>My content</p>
        HTML;

        yield 'with partial HTML and naive manager' => [
            new NaiveHtmlCrawlerManager(),
            $html,
            $expected,
        ];

        yield 'with partial HTML and shared manager' => [
            new SharedHtmlCrawlerManager(),
            $html,
            $expected,
        ];
    }
}
