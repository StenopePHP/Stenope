<?php

/*
 * This file is part of the "StenopePHP/Stenope" bundle.
 *
 * @author Thomas Jarrand <thomas.jarrand@gmail.com>
 */

namespace Stenope\Bundle\Highlighter;

use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Stenope\Bundle\Behaviour\HighlighterInterface;
use Symfony\Component\Process\InputStream;
use Symfony\Component\Process\Process;
use Symfony\Component\Stopwatch\Stopwatch;

/**
 * Prism code highlight
 */
class Prism implements HighlighterInterface
{
    private const IDLE_TIMEOUT = 60;

    private string $executable;
    private ?Process $server = null;
    private ?InputStream $input = null;
    private ?Stopwatch $stopwatch;
    private LoggerInterface $logger;

    public function __construct(?string $executable = null, ?Stopwatch $stopwatch = null, ?LoggerInterface $logger = null)
    {
        $this->executable = $executable ?? __DIR__ . '/../../dist/bin/prism.js';
        $this->stopwatch = $stopwatch;
        $this->logger = $logger ?? new NullLogger();
    }

    public function start(): void
    {
        if (!$this->server) {
            $this->input = new InputStream();
            $this->server = new Process(['node', $this->executable], null, null, $this->input, null);
        }

        if (!$this->server->isRunning()) {
            $this->server->start();
        }

        $this->server->setIdleTimeout(self::IDLE_TIMEOUT);
    }

    public function stop(): void
    {
        if (!$this->server || !$this->server->isRunning()) {
            return;
        }

        $this->server->stop();
        $this->server = null;
        $this->input->close();
    }

    /**
     * Highlight a portion of code with pygmentize
     */
    public function highlight(string $value, string $language): string
    {
        if ($this->stopwatch) {
            $event = $this->stopwatch->start('highlight', 'stenope');
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

        // Code highlight was processed.
        // Let's remove the idle timeout for the running server until next call:
        $this->server->setIdleTimeout(null);

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
