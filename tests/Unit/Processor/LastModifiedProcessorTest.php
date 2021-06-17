<?php

/*
 * This file is part of the "StenopePHP/Stenope" bundle.
 *
 * @author Thomas Jarrand <thomas.jarrand@gmail.com>
 */

namespace Stenope\Bundle\Tests\Unit\Processor;

use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Stenope\Bundle\Content;
use Stenope\Bundle\Processor\LastModifiedProcessor;
use Stenope\Bundle\Provider\Factory\LocalFilesystemProviderFactory;
use Stenope\Bundle\Service\Git\LastModifiedFetcher;

class LastModifiedProcessorTest extends TestCase
{
    use ProphecyTrait;

    public function testFromContent(): void
    {
        $processor = new LastModifiedProcessor('lastModified');

        $data = [];
        $content = new Content('slug', 'type', 'content', 'format', new \DateTimeImmutable('2021-06-14 10:25:47 +0200'));

        $processor->__invoke($data, $content);

        self::assertInstanceOf(\DateTimeImmutable::class, $data['lastModified']);
        self::assertEquals($content->getLastModified(), $data['lastModified']);
    }

    public function testFromGit(): void
    {
        $gitFetcher = $this->prophesize(LastModifiedFetcher::class);
        $gitFetcher->__invoke(Argument::type('string'))
            ->willReturn($expectedDate = new \DateTimeImmutable('2021-05-10 10:00:00 +0000'))
            ->shouldBeCalledOnce()
        ;

        $processor = new LastModifiedProcessor('lastModified', $gitFetcher->reveal());

        $data = [];
        $content = new Content('slug', 'type', 'content', 'format', new \DateTimeImmutable('2021-06-14 10:25:47 +0200'), new \DateTimeImmutable(), [
            'provider' => LocalFilesystemProviderFactory::TYPE,
            'path' => 'some-path.md',
        ]);

        $processor->__invoke($data, $content);

        self::assertInstanceOf(\DateTimeImmutable::class, $data['lastModified']);
        self::assertEquals($expectedDate, $data['lastModified']);
    }

    public function testWontUseGitOnNonFilesProvider(): void
    {
        $gitFetcher = $this->prophesize(LastModifiedFetcher::class);
        $gitFetcher->__invoke(Argument::type('string'))->shouldNotBeCalled();

        $processor = new LastModifiedProcessor('lastModified', $gitFetcher->reveal());

        $data = [];
        $content = new Content('slug', 'type', 'content', 'format', new \DateTimeImmutable('2021-06-14 10:25:47 +0200'));

        $processor->__invoke($data, $content);

        self::assertInstanceOf(\DateTimeImmutable::class, $data['lastModified']);
        self::assertEquals($content->getLastModified(), $data['lastModified']);
    }
}
