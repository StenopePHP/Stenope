<?php

/*
 * This file is part of the "StenopePHP/Stenope" bundle.
 *
 * @author Thomas Jarrand <thomas.jarrand@gmail.com>
 */

namespace Stenope\Bundle\Tests\Unit\Service\Git;

use PHPUnit\Framework\TestCase;
use Psr\Log\Test\TestLogger;
use Stenope\Bundle\Service\Git\LastModifiedFetcher;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\PhpExecutableFinder;

class LastModifiedFetcherTest extends TestCase
{
    private static string $php;
    private static string $executable;

    public static function setUpBeforeClass(): void
    {
        self::$php = (new PhpExecutableFinder())->find();
        self::$executable = self::$php . ' ' . FIXTURES_DIR . '/Unit/Service/Git/bin/git.php';
    }

    public function testDisabled(): void
    {
        $fetcher = new LastModifiedFetcher(null);
        $fetcher->reset();

        self::assertNull($fetcher->__invoke('some-fake-path'));
    }

    public function testUnavailable(): void
    {
        $logger = new TestLogger();
        $fetcher = new LastModifiedFetcher('not-valid-path', $logger);
        $fetcher->reset();

        self::assertNull($fetcher->__invoke('some-fake-path'));
        self::assertTrue($logger->hasWarningThatContains('Git was not found at path'));
        self::assertCount(1, $logger->records);

        self::assertNull($fetcher->__invoke('some-fake-path'));
        self::assertCount(1, $logger->records, 'Do not attempt to check git avaiulability twice');
    }

    public function testSuccess(): void
    {
        $logger = new TestLogger();
        $fetcher = new LastModifiedFetcher(self::$executable, $logger);
        $fetcher->reset();

        self::assertInstanceOf(\DateTimeImmutable::class, $date = $fetcher->__invoke('some-fake-path'));
        self::assertSame('2021-06-14T10:25:47+02:00', $date->format(\DateTimeImmutable::RFC3339));
    }

    public function testEmpty(): void
    {
        $logger = new TestLogger();
        $fetcher = new LastModifiedFetcher(self::$executable, $logger);
        $fetcher->reset();

        self::assertNull($fetcher->__invoke('empty'));
    }

    public function testFailure(): void
    {
        $logger = new TestLogger();
        $fetcher = new LastModifiedFetcher(self::$executable, $logger);
        $fetcher->reset();

        $this->expectException(ProcessFailedException::class);

        $fetcher->__invoke('fail');
    }
}
