<?php

/*
 * This file is part of the "StenopePHP/Stenope" bundle.
 *
 * @author Thomas Jarrand <thomas.jarrand@gmail.com>
 */

namespace Stenope\Bundle\Tests\Integration;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class ResolvedLinkedContentsTest extends WebTestCase
{
    private static array $kernelOptions = [
        'environment' => 'prod',
        'debug' => true,
    ];

    public function testItResolvesCrossLinkedContents(): void
    {
        $client = self::createClient(self::$kernelOptions);
        $client->request('GET', '/recipes/ogito');

        self::assertResponseIsSuccessful();
        self::assertStringContainsString(
            <<<HTML
            Cheers <a href="/authors/ogi">Ogi</a> for this recipe.
            Check his <a href="/authors/ogi#recipes">other recipes</a>.
            HTML,
            $client->getResponse()->getContent(),
            'Response contains proper link to author'
        );
    }
}
