<?php

/*
 * This file is part of the "Tom32i/Content" bundle.
 *
 * @author Thomas Jarrand <thomas.jarrand@gmail.com>
 */

namespace Content\Highlighter;

use Content\Behaviour\HighlighterInterface;
use Symfony\Component\Process\Process;

/**
 * Prism code highlight
 */
class Prism implements HighlighterInterface
{
    private string $executable;

    public function __construct(string $executable = __DIR__ . '/../Resources/node/prism.js')
    {
        $this->executable = $executable;
    }

    /**
     * Highlight a portion of code with pygmentize
     */
    public function highlight(string $value, string $language): string
    {
        $process = new Process(['node', $this->executable, $language, $value]);

        $process->run();

        if (!$process->isSuccessful()) {
            throw new \RuntimeException($process->getErrorOutput());
        }

        return trim($process->getOutput());
    }
}
