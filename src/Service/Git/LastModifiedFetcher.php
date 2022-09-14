<?php

/*
 * This file is part of the "StenopePHP/Stenope" bundle.
 *
 * @author Thomas Jarrand <thomas.jarrand@gmail.com>
 */

namespace Stenope\Bundle\Service\Git;

use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;
use Symfony\Contracts\Service\ResetInterface;

class LastModifiedFetcher implements ResetInterface
{
    /** Git executable path on the system / PATH used to get the last commit date for the file, or null to disable. */
    private ?string $gitPath;
    private LoggerInterface $logger;

    private static ?bool $gitAvailable = null;

    public function __construct(
        ?string $gitPath = 'git',
        ?LoggerInterface $logger = null
    ) {
        $this->gitPath = $gitPath;
        $this->logger = $logger ?? new NullLogger();
    }

    /**
     * @throws ProcessFailedException
     */
    public function __invoke(string $filePath): ?\DateTimeImmutable
    {
        if (null === $this->gitPath || false === self::$gitAvailable) {
            // Don't go further if the git command is not available or the git feature is disabled
            return null;
        }

        $executable = explode(' ', $this->gitPath);

        if (null === self::$gitAvailable) {
            // Check once if the git command is available
            $process = new Process([...$executable, '--version']);
            $process->run();

            if (!$process->isSuccessful()) {
                self::$gitAvailable = false;

                $this->logger->warning('Git was not found at path "{gitPath}". Check the binary path is correct or part of your PATH.', [
                    'gitPath' => $this->gitPath,
                    'output' => $process->getOutput(),
                    'err_output' => $process->getErrorOutput(),
                ]);

                return null;
            }
        }

        if (null === self::$gitAvailable) {
            // Check once if the project is a git repository
            $process = new Process([...$executable, 'rev-parse', '--is-inside-work-tree']);
            $process->run();

            if (!$process->isSuccessful()) {
                self::$gitAvailable = false;

                $this->logger->warning('The current project is not a git repository. Last modified date will not be available using the LastModifiedFetcher.', [
                    'output' => $process->getOutput(),
                    'err_output' => $process->getErrorOutput(),
                ]);

                return null;
            }

            self::$gitAvailable = true;
        }

        $process = new Process([...$executable, 'log', '-1', '--format=%cd', '--date=iso', $filePath]);
        $process->run();

        if (!$process->isSuccessful()) {
            throw new ProcessFailedException($process);
        }

        if ($output = $process->getOutput()) {
            return new \DateTimeImmutable(trim($output));
        }

        return null;
    }

    public function reset(): void
    {
        self::$gitAvailable = null;
    }
}
