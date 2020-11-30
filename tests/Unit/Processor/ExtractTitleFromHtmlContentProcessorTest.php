<?php

/*
 * This file is part of the "StenopePHP/Stenope" bundle.
 *
 * @author Thomas Jarrand <thomas.jarrand@gmail.com>
 */

namespace Stenope\Bundle\Tests\Unit\Processor;

use PHPUnit\Framework\TestCase;
use Stenope\Bundle\Content;
use Stenope\Bundle\Processor\ExtractTitleFromHtmlContentProcessor;

class ExtractTitleFromHtmlContentProcessorTest extends TestCase
{
    public function testDefaults(): void
    {
        $data = [
            'content' => <<<HTML
            <h1>If you wrestle or remain with a new affirmation, density feels you.</h1>

            <h2>Foo</h2>

            Lorem ipsum

            <h1>Not the first title</h1>
            HTML,
        ];

        $processor = new ExtractTitleFromHtmlContentProcessor();
        $processor->__invoke($data, \stdClass::class, $this->getDummyContent());

        self::assertSame('If you wrestle or remain with a new affirmation, density feels you.', $data['title']);
    }

    public function testConfiguredProperties(): void
    {
        $data = [
            'html' => <<<HTML
            <h1>If you wrestle or remain with a new affirmation, density feels you.</h1>

            Lorem ipsum
            HTML,
        ];

        $processor = new ExtractTitleFromHtmlContentProcessor('html', 'name');
        $processor->__invoke($data, \stdClass::class, $this->getDummyContent());

        self::assertSame('If you wrestle or remain with a new affirmation, density feels you.', $data['name']);
    }

    public function testWithoutH1(): void
    {
        $data = [
            'content' => <<<HTML
            <h2>Foo</h2>

            Lorem ipsum
            HTML,
        ];

        $processor = new ExtractTitleFromHtmlContentProcessor();
        $processor->__invoke($data, \stdClass::class, $this->getDummyContent());

        self::assertSame($data, $data, 'data are unchanged, no title set.');
    }

    /**
     * @dataProvider provideIgnoresWhenNoProperContentAvailable
     */
    public function testIgnoresWhenNoProperContentAvailable(array $data): void
    {
        $processor = new ExtractTitleFromHtmlContentProcessor();
        $processor->__invoke($data, \stdClass::class, $this->getDummyContent());

        self::assertSame($data, $data, 'data are unchanged');
    }

    public function provideIgnoresWhenNoProperContentAvailable()
    {
        yield 'not content set' => [[]];
        yield 'not string' => [['content' => new \stdClass()]];
        yield 'not html' => [['content' => '{ "foo" : "bar" }']];
    }

    public function testIgnoresWhenTitleAlreadySet(): void
    {
        $data = ['title' => 'Expected title', 'content' => '<h1>Another title</h1>'];

        $processor = new ExtractTitleFromHtmlContentProcessor();
        $processor->__invoke($data, \stdClass::class, $this->getDummyContent());

        self::assertSame($data, $data, 'data are unchanged');
    }

    private function getDummyContent(): Content
    {
        return new Content('slug', \stdClass::class, 'content', 'markdown');
    }
}
