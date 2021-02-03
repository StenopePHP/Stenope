<?php

/*
 * This file is part of the "StenopePHP/Stenope" bundle.
 *
 * @author Thomas Jarrand <thomas.jarrand@gmail.com>
 */

namespace Stenope\Bundle\Tests\Unit\Processor;

use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Stenope\Bundle\Content;
use Stenope\Bundle\ContentManager;
use Stenope\Bundle\Processor\ResolveContentLinksProcessor;
use Stenope\Bundle\ReverseContent\RelativeLinkContext;
use Stenope\Bundle\Routing\ContentUrlResolver;

class ResolveContentLinksProcessorTest extends TestCase
{
    use ProphecyTrait;

    public function testResolveLinks(): void
    {
        $data = [
            'content' => <<<HTML
            <a href="/absolute-link">Don't change this</a>
            <a href="http://external.com">Don't change this</a>
            <a href="//external.com">Don't change this</a>
            <a href="#anchor">Don't change this</a>

            <a href="../other-contents/another-content.md">Another content</a>
            HTML,
        ];

        $currentContent = new Content('some-content', 'SomeContent', 'rawContent', 'markdown', null, null, [
            'path' => '/workspace/project/content/current.md',
            'provider' => 'files',
        ]);

        $urlGenerator = $this->prophesize(ContentUrlResolver::class);

        $processor = new ResolveContentLinksProcessor($urlGenerator->reveal());

        $manager = $this->prophesize(ContentManager::class);
        $processor->setContentManager($manager->reveal());

        $manager->reverseContent(new RelativeLinkContext(
            $currentContent->getMetadata(),
            '../other-contents/another-content.md',
        ))->shouldBeCalledOnce()->willReturn(
            $resolvedContent = new Content('some-content', 'AnotherContent', 'rawContent', 'markdown', null, null, [
                'path' => '/workspace/project/other-contents/another-content.md',
            ])
        );

        $urlGenerator->resolveUrl($resolvedContent)->shouldBeCalledOnce()->willReturn('/other-contents-route-path/another-contents');

        $processor->__invoke($data, \stdClass::class, $currentContent);

        self::assertXmlStringEqualsXmlString(<<<HTML
            <body>
                <a href="/absolute-link">Don't change this</a>
                <a href="http://external.com">Don't change this</a>
                <a href="//external.com">Don't change this</a>
                <a href="#anchor">Don't change this</a>
                <a href="/other-contents-route-path/another-contents">Another content</a>
            </body>
            HTML,
            $data['content']
        );
    }
}
