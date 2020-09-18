<?php

/*
 * This file is part of the "Tom32i/Content" bundle.
 *
 * @author Thomas Jarrand <thomas.jarrand@gmail.com>
 */

namespace Content\Highlighter;

use Content\Behaviour\HighlighterInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
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
    private LoggerInterface $logger;

    public function __construct(?string $executable = null, ?Stopwatch $stopwatch = null, ?LoggerInterface $logger = null)
    {
        $this->executable = $executable ?? __DIR__ . '/../Resources/dist/bin/prism.js';
        $this->stopwatch = $stopwatch;
        $this->logger = $logger ?? new NullLogger();
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

        $errors = [];

        $this->server->waitUntil(function ($type, $output) use (&$errors) {
            $lines = array_filter(explode(PHP_EOL, $output));

            if ($type === Process::ERR) {
                foreach ($lines as $line) {
                    if ($line === 'DONE') {
                        return true;
                    }

                    $errors[] = $line;
                }
            }

            return false;
        });

        if (isset($event)) {
            $event->stop();
        }

        if (\count($errors) > 0) {
            foreach ($errors as $error) {
                $this->logger->error('Highlight error: {message}', ['message' => $error]);
            }

            return $value;
        }

        return $this->server->getIncrementalOutput();
    }
}
