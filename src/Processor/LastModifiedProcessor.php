<?php

/*
 * This file is part of the "StenopePHP/Stenope" bundle.
 *
 * @author Thomas Jarrand <thomas.jarrand@gmail.com>
 */

namespace Stenope\Bundle\Processor;

use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Stenope\Bundle\Behaviour\ProcessorInterface;
use Stenope\Bundle\Content;
use Stenope\Bundle\Provider\Factory\LocalFilesystemProviderFactory;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

/**
 * Set a "LastModified" property based on the last modified date set by the provider.
 * E.g, for the {@see LocalFilesystemProvider}, the file mtime on the filesystem.
 *
 * If available, for local files, it'll use Git to get the last commit date for this file.
 */
class LastModifiedProcessor implements ProcessorInterface
{
    private string $property;
    /** Git executable path on the system / PATH used to get the last commit date for the file, or null to disable. */
    private ?string $gitPath;
    private LoggerInterface $logger;

    private static ?bool $gitAvailable = null;

    public function __construct(
        string $property = 'lastModified',
        ?string $gitPath = 'git',
        ?LoggerInterface $logger = null
    ) {
        $this->property = $property;
        $this->gitPath = $gitPath;
        $this->logger = $logger ?? new NullLogger();
    }

    public function __invoke(array &$data, Content $content): void
    {
        if (\array_key_exists($this->property, $data)) {
            // Last modified already set (even if explicitly set as null).
            return;
        }

        $data[$this->property] = $content->getLastModified();

        if (LocalFilesystemProviderFactory::TYPE !== ($content->getMetadata()['provider'] ?? null)) {
            // Won't attempt with a non local filesystem content.
            return;
        }

        if (null === $this->gitPath || false === self::$gitAvailable) {
            // Don't go further if the git command is not available or the git feature is disabled
            return;
        }

        if (null === self::$gitAvailable) {
            // Check once if the git command is available
            $process = new Process([$this->gitPath, '--version']);
            $process->run();

            if (!$process->isSuccessful()) {
                self::$gitAvailable = false;

                $this->logger->notice('Git was not found at path "{gitPath}". Check the binary path is correct or part of your PATH.', [
                    'gitPath' => $this->gitPath,
                    'output' => $process->getOutput(),
                    'err_output' => $process->getErrorOutput(),
                ]);

                return;
            }

            self::$gitAvailable = true;
        }

        $filePath = $content->getMetadata()['path'];
        $process = new Process([$this->gitPath, 'log', '-1', '--format=%cd', '--date=iso', $filePath]);
        $process->run();

        if (!$process->isSuccessful()) {
            throw new ProcessFailedException($process);
        }

        if ($output = $process->getOutput()) {
            $data[$this->property] = new \DateTimeImmutable($output);
        }
    }
}
