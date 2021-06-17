<?php

/*
 * This file is part of the "StenopePHP/Stenope" bundle.
 *
 * @author Thomas Jarrand <thomas.jarrand@gmail.com>
 */

namespace Stenope\Bundle\Processor;

use Stenope\Bundle\Behaviour\ProcessorInterface;
use Stenope\Bundle\Content;
use Stenope\Bundle\Provider\Factory\LocalFilesystemProviderFactory;
use Stenope\Bundle\Service\Git\LastModifiedFetcher;

/**
 * Set a "LastModified" property based on the last modified date set by the provider.
 * E.g, for the {@see LocalFilesystemProvider}, the file mtime on the filesystem.
 *
 * If available, for local files, it'll use Git to get the last commit date for this file.
 */
class LastModifiedProcessor implements ProcessorInterface
{
    private string $property;
    private ?LastModifiedFetcher $gitLastModified;

    public function __construct(string $property = 'lastModified', ?LastModifiedFetcher $gitLastModified = null)
    {
        $this->property = $property;
        $this->gitLastModified = $gitLastModified;
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

        if (null === $this->gitLastModified) {
            return;
        }

        $filePath = $content->getMetadata()['path'];

        if ($lastModified = ($this->gitLastModified)($filePath)) {
            $data[$this->property] = $lastModified;
        }
    }
}
