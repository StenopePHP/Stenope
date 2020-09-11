<?php

/*
 * This file is part of the "Tom32i/Content" bundle.
 *
 * @author Thomas Jarrand <thomas.jarrand@gmail.com>
 */

namespace Content\Highlighter;

use Content\Behaviour\HighlighterInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Component\Process\Process;

/**
 * Prism code highlight
 */
class PrismApi implements HighlighterInterface
{
    private HttpClientInterface $client;
    private string $executable;
    private string $host;
    private string $port;
    private ?Process $server = null;

    public function __construct(
        HttpClientInterface $client,
        string $executable = __DIR__ . '/../Resources/node/prism.api.js',
        string $host = '127.0.0.1',
        string $port = '8032'
    ) {
        $this->client = $client;
        $this->executable = $executable;
        $this->host = $host;
        $this->port = $port;

        $this->start();
    }

    public function start()
    {
        if (!$this->server) {
            $this->server = new Process(['node', $this->executable, $this->host, $this->port]);
        }

        $this->server->start();
        //$this->server->wait();
    }

    public function stop()
    {
        if (!$this->server || !$this->server->isRunning()) {
            return;
        }

        $this->server->stop();
    }

    /**
     * Highlight a portion of code with pygmentize
     */
    public function highlight(string $value, string $language): string
    {
        $response = $this->client->request(
            'POST',
            sprintf('http://%s:%s', $this->host, $this->port),
            ['json' => ['language' => $language, 'value' => $value]]
        );

        if ($response->getStatusCode() !== 200) {
            throw new \RuntimeException($response->getContent());
        }

        return $response->getContent();
    }
}
