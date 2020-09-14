<?php

/*
 * This file is part of the "Tom32i/Content" bundle.
 *
 * @author Thomas Jarrand <thomas.jarrand@gmail.com>
 */

namespace Content\Highlighter;

use Content\Behaviour\HighlighterInterface;
use Symfony\Component\Process\InputStream;
use Symfony\Component\Process\Process;
use Symfony\Component\Stopwatch\Stopwatch;

/**
 * Prism code highlight
 */
class Prism implements HighlighterInterface
{
    private string $executable;
    private ?Process $server = null;
    private ?InputStream $input = null;
    private ?Stopwatch $stopwatch;

    public function __construct(?string $executable = null, ?Stopwatch $stopwatch = null)
    {
        $this->executable = $executable ?? __DIR__ . '/../Resources/dist/bin/prism.js';
        $this->stopwatch = $stopwatch;
    }

    public function start(): void
    {
        if (!$this->server) {
            $this->input = new InputStream();
            $this->server = new Process(['node', $this->executable], null, null, $this->input);
        }

        if (!$this->server->isRunning()) {
            $this->server->start();
        }
    }

    public function stop(): void
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
        if ($this->stopwatch) {
            $event = $this->stopwatch->start('highlight', 'content');
        }

        $this->start();

        $this->input->write(
            json_encode(['language' => $language, 'value' => $value]) . PHP_EOL
        );

        $this->server->waitUntil(function ($type, $output) {
            return $type === Process::ERR && $output === 'DONE';
        });

        $output = $this->server->getIncrementalOutput();

        if (isset($event)) {
            $event->stop();
        }

        return $output;
    }
}
