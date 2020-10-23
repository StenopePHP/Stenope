<?php

/*
 * This file is part of the "StenopePHP/Stenope" bundle.
 *
 * @author Thomas Jarrand <thomas.jarrand@gmail.com>
 */

namespace Stenope\Bundle\Highlighter;

use Stenope\Bundle\Behaviour\HighlighterInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Process\Process;

/**
 * Pygments code highlight
 */
class Pygments implements HighlighterInterface
{
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

    public function __construct(string $temporaryPath = null)
    {
        $this->temporaryPath = $temporaryPath ?: sys_get_temp_dir();
        $this->files = new Filesystem();
    }

    /**
     * Is pygmentize available?
     */
    public static function isAvailable(): bool
    {
        $process = new Process('pygmentize -V');

        $process->run();

        return $process->isSuccessful();
    }

    /**
     * Highlight a portion of code with pygmentize
     */
    public function highlight(string $value, string $language): string
    {
        $path = tempnam($this->temporaryPath, 'pyg');

        if ($language === 'php' && substr($value, 0, 5) !== '<?php') {
            $value = '<?php ' . PHP_EOL . $value;
        }

        $this->files->dumpFile($path, $value);

        $value = $this->execute($language, $path);

        unlink($path);

        if (preg_match('#^<div class="highlight"><pre>#', $value) && preg_match('#</pre></div>$#', $value)) {
            return substr($value, 28, \strlen($value) - 40);
        }

        return $value;
    }

    /**
     * Run 'pygmentize' command on the given file
     */
    private function execute(string $language, string $path): string
    {
        $process = Process::fromShellCommandline(sprintf('pygmentize -f html -l %s %s', $language, $path));

        $process->run();

        if (!$process->isSuccessful()) {
            throw new \RuntimeException($process->getErrorOutput());
        }

        return trim($process->getOutput());
    }
}
