<?php

/*
 * This file is part of the "StenopePHP/Stenope" bundle.
 *
 * @author Thomas Jarrand <thomas.jarrand@gmail.com>
 */

namespace Stenope\Bundle\Tests\Unit\Service;

use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Stenope\Bundle\Service\AssetUtils;
use Symfony\Component\Asset\Packages;

class AssetsUtilsTest extends TestCase
{
    use ProphecyTrait;

    private AssetUtils $utils;

    protected function setUp(): void
    {
        $packages = $this->prophesize(Packages::class);
        $packages->getUrl(Argument::type('string'))->will(function (array $args): string {
            [$url] = $args;

            return "https://cnd.example.com/$url";
        });

        $this->utils = new AssetUtils($packages->reveal());
    }

    /**
     * @dataProvider provideGetUrlData
     */
    public function testGetUrl(string $url, string $expected): void
    {
        self::assertSame($expected, $this->utils->getUrl($url));
    }

    public function provideGetUrlData(): iterable
    {
        yield ['mailto:foo@exemple.com', 'mailto:foo@exemple.com'];
        yield ['tel:+33606060606', 'tel:+33606060606'];
        yield ['file:///foo.svg', 'file:///foo.svg'];
        yield ['http://example.com/bar', 'http://example.com/bar'];
        yield ['//example.com/bar', '//example.com/bar'];
        yield ['https://example.com/bar', 'https://example.com/bar'];
        yield ['foo.png', 'https://cnd.example.com/foo.png'];
    }
}
