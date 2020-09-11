<?php

/*
 * This file is part of the "Tom32i/Content" bundle.
 *
 * @author Thomas Jarrand <thomas.jarrand@gmail.com>
 */

namespace Content\Highlighter;

use Content\Behaviour\HighlighterInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * Prism code highlight
 */
class PrismApi implements HighlighterInterface
{
    /**
     * Script path
     */
    private string $executable;

    private HttpClientInterface $client;

    public function __construct(HttpClientInterface $client, string $executable = __DIR__ . '/../Resources/node/prism.api.js')
    {
        $this->client = $client;
        $this->executable = $executable;
    }

    /**
     * Highlight a portion of code with pygmentize
     */
    public function highlight(string $value, string $language): string
    {
        $response = $this->client->request(
            'POST',
            'http://localhost:8032',
            ['json' => ['language' => $language, 'value' => $value]]
        );

        if ($response->getStatusCode() !== 200) {
            return $value;
        }

        return $response->getContent();
    }
}
