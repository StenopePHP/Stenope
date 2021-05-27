<?php

/*
 * This file is part of the "StenopePHP/Stenope" bundle.
 *
 * @author Thomas Jarrand <thomas.jarrand@gmail.com>
 */

namespace Stenope\Bundle\Tests\Integration;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class TableOfContentTest extends WebTestCase
{
    private static array $kernelOptions = [
        'environment' => 'prod',
        'debug' => true,
    ];

    public function testItResolvesTableOfContent(): void
    {
        $client = self::createClient(self::$kernelOptions);
        $client->request('GET', '/recipes/ogito');

        self::assertResponseIsSuccessful();

        self::assertSelectorExists('.table-of-content');
        self::assertSelectorTextContains('.table-of-content > ol > li:nth-child(1)', 'First step');
        self::assertSelectorTextContains('.table-of-content > ol > li:nth-child(1) li', 'Sub-step');
        self::assertSelectorTextContains('.table-of-content > ol > li:nth-child(2)', 'Second step');
    }

    public function testItResolvesTableOfContentWithLimit(): void
    {
        $client = self::createClient(self::$kernelOptions);
        $client->request('GET', '/recipes/cheesecake');

        self::assertResponseIsSuccessful();

        self::assertSelectorExists('.table-of-content');
        self::assertSelectorTextContains('.table-of-content > ol > li:nth-child(1)', 'First step');
        self::assertSelectorNotExists('.table-of-content > ol > li:nth-child(1) li');
        self::assertSelectorTextContains('.table-of-content > ol > li:nth-child(2)', 'Second step');
    }

    public function testDisabledTableOfContent(): void
    {
        $client = self::createClient(self::$kernelOptions);
        $client->request('GET', '/recipes/tomiritsu');

        self::assertResponseIsSuccessful();

        self::assertSelectorNotExists('.table-of-content');
    }
}
