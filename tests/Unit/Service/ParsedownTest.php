<?php

/*
 * This file is part of the "StenopePHP/Stenope" bundle.
 *
 * @author Thomas Jarrand <thomas.jarrand@gmail.com>
 */

namespace Stenope\Bundle\Tests\Unit\Service;

use PHPUnit\Framework\TestCase;
use Stenope\Bundle\Service\Parsedown;

class ParsedownTest extends TestCase
{
    private Parsedown $parser;

    protected function setUp(): void
    {
        $this->parser = new Parsedown();
    }

    /**
     * @dataProvider provideAdmonitions
     */
    public function testAdmonitions(string $markdown, string $expectedHtml): void
    {
        $html = $this->parser->parse($markdown);

        self::assertHtmlEquals($expectedHtml, $html);
    }

    public function provideAdmonitions(): iterable
    {
        yield 'basic' => [
            <<<MARKDOWN
            !!! Note
                Going to the small mind doesn’t facilitate zen anymore
                than emerging creates superior blessing.
            MARKDOWN,
            <<<HTML
            <div class="admonition note">
                <p class="admonition-title">Note</p>
                <p>
                Going to the small mind doesn’t facilitate zen anymore
                than emerging creates superior blessing.
            </p>
            </div>
            HTML
        ];
        yield 'basic with markdown inside' => [
            <<<MARKDOWN
            !!! Note
                Going to the `small mind doesn’t facilitate` zen anymore
                than emerging *creates* superior _blessing_.
            MARKDOWN,
            <<<HTML
            <div class="admonition note">
                <p class="admonition-title">Note</p>
                <p>
                Going to the <code class="code-inline">small mind doesn&#x2019;t facilitate</code> zen anymore
                than emerging <em>creates</em> superior <em>blessing</em>.
            </p>
            </div>
            HTML
        ];

        yield 'basic with text around' => [
            <<<MARKDOWN
            Some text before

            !!! Note
                Going to the small mind doesn’t facilitate zen anymore
                than emerging creates superior blessing.

            Some text after
            MARKDOWN,
            <<<HTML
            <p>Some text before</p>
            <div class="admonition note">
                <p class="admonition-title">Note</p>
                <p>
                Going to the small mind doesn’t facilitate zen anymore
                than emerging creates superior blessing.
            </p>
            </div>
            <p>Some text after</p>
            HTML
        ];

        yield 'basic (without spacing around)' => [
            <<<MARKDOWN
            Some text before
            !!! Note
                Going to the small mind doesn’t facilitate zen anymore
                than emerging creates superior blessing.
            Some text after
            MARKDOWN,
            <<<HTML
            <p>Some text before</p>
            <div class="admonition note">
                <p class="admonition-title">Note</p>
            <p>
                Going to the small mind doesn’t facilitate zen anymore
                than emerging creates superior blessing.
            </p>
            </div>
            <p>Some text after</p>
            HTML
        ];

        yield 'with explicit title' => [
            <<<MARKDOWN
            !!! Note "Note title"
                Going to the small mind doesn’t facilitate zen anymore
                than emerging creates superior blessing.
            MARKDOWN,
            <<<HTML
            <div class="admonition note">
                <p class="admonition-title">Note title</p>
            <p>
                Going to the small mind doesn’t facilitate zen anymore
                than emerging creates superior blessing.
            </p>
            </div>
            HTML
        ];

        yield 'with explicit title with markdown inside' => [
            <<<MARKDOWN
            !!! Note "Note `title`"
                Going to the small mind doesn’t facilitate zen anymore
                than emerging creates superior blessing.
            MARKDOWN,
            <<<HTML
            <div class="admonition note">
                <p class="admonition-title">Note <code class="code-inline">title</code></p>
            <p>
                Going to the small mind doesn’t facilitate zen anymore
                than emerging creates superior blessing.
            </p>
            </div>
            HTML
        ];

        yield 'with no title' => [
            <<<MARKDOWN
            !!! Note ""
                Going to the small mind doesn’t facilitate zen anymore
                than emerging creates superior blessing.
            MARKDOWN,
            <<<HTML
            <div class="admonition note">
            <p>
                Going to the small mind doesn’t facilitate zen anymore
                than emerging creates superior blessing.
            </p>
            </div>
            HTML
        ];

        yield 'multiple classes with title' => [
            <<<MARKDOWN
            !!! Note foo bar "Note title"
                Going to the small mind doesn’t facilitate zen anymore
                than emerging creates superior blessing.
            MARKDOWN,
            <<<HTML
            <div class="admonition note foo bar">
                <p class="admonition-title">Note title</p>
            <p>
                Going to the small mind doesn’t facilitate zen anymore
                than emerging creates superior blessing.
            </p>
            </div>
            HTML
        ];
    }

    public static function assertHtmlEquals(string $expected, string $actual, ?string $message = ''): void
    {
        self::assertXmlStringEqualsXmlString("<root>$expected</root>", "<root>$actual</root>", $message);
    }
}
