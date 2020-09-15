<?php

/*
 * This file is part of the "Tom32i/Content" bundle.
 *
 * @author Thomas Jarrand <thomas.jarrand@gmail.com>
 */

namespace Content\Command;

use Psr\Log\LoggerInterface;
use Psr\Log\LoggerTrait;
use Psr\Log\LogLevel;
use Symfony\Component\Console\Style\StyleInterface;

class BuildCommandLogger implements LoggerInterface
{
    use LoggerTrait;

    private StyleInterface $io;

    public function __construct(StyleInterface $io)
    {
        $this->io = $io;
    }

    public function log($level, $message, array $context = []): void
    {
        switch ($level) {
            case LogLevel::ERROR:
                $this->io->error($message);
                break;

            case LogLevel::WARNING:
                $this->io->warning($message);
                break;

            case LogLevel::INFO:
                $this->io->section($message);
                break;

            case LogLevel::DEBUG:
                if (isset($context['success'])) {
                    $this->io->success(str_repeat(' ', 4) . $message);
                } else {
                    $this->io->text(str_repeat(' ', 4) . $message);
                }
                break;

            default:
                $this->io->text($message);
                break;
        }
    }
}
