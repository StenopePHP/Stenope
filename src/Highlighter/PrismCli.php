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
use Symfony\Component\Process\InputStream;

/**
 * Prism code highlight
 */
class PrismCli implements HighlighterInterface
{
    private string $executable;
    private ?Process $server = null;
    private ?InputStream $input = null;

    public function __construct(string $executable = __DIR__ . '/../Resources/node/prism.cli.js') {
        $this->executable = $executable;
    }

    public function start()
    {
        if (!$this->server) {
            $this->input = new InputStream();
            $this->server = new Process(['node', $this->executable], null, null, $this->input);
            $this->server->setTimeout(0.0);
            $this->server->setIdleTimeout(0.0);
        }

        if (!$this->server->isRunning()) {
            $this->server->start();
        }
    }

    public function stop()
    {
        if (!$this->server || !$this->server->isRunning()) {
            return;
        }

        $this->server->stop();
        $this->input->close();
    }

    /**
     * Highlight a portion of code with pygmentize
     */
    public function highlight(string $value, string $language): string
    {
        $this->start();

        $this->input->write(
            json_encode(['language' => $language, 'value' => $value])// . PHP_EOL
        );

        $this->server->waitUntil(function ($type, $output) {
            //dump($type, $output);
            return true;
        });

        return $this->server->getOutput();
    }
}
