<?php

/*
 * This file is part of the "Tom32i/Content" bundle.
 *
 * @author Thomas Jarrand <thomas.jarrand@gmail.com>
 */

namespace Content\Highlighter;

use Content\Behaviour\HighlighterInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Process\Process;

/**
 * Prism code highlight
 */
class Prism implements HighlighterInterface
{
    /**
     * Script path
     *
     * @var string
     */
    private $executable;

    /**
     * File system
     *
     * @var FileSystem
     */
    private $files;

    /**
     * Temporary directory path
     *
     * @var string
     */
    private $temporaryPath;

    public function __construct(string $executable = __DIR__ . '/../Resources/node/prism.js', string $temporaryPath = null)
    {
        $this->executable = $executable;
        $this->temporaryPath = $temporaryPath ?: sys_get_temp_dir();
        $this->files = new Filesystem();
    }

    /**
     * Highlight a portion of code with pygmentize
     */
    public function highlight(string $value, string $language): string
    {
        $path = tempnam($this->temporaryPath, 'pri');

        $this->files->dumpFile($path, $value);

        $value = $this->execute($language, $path);

        unlink($path);

        return $value;
    }

    /**
     * Run 'prism.js' command on the given file
     */
    private function execute(string $language, string $path): string
    {
        $process = Process::fromShellCommandline(sprintf('node %s %s "%s"', $this->executable, $language, $path));

        $process->run();

        if (!$process->isSuccessful()) {
            throw new \RuntimeException($process->getErrorOutput());
        }

        return trim($process->getOutput());
    }
}
