<?php

/*
 * This file is part of the "StenopePHP/Stenope" bundle.
 *
 * @author Thomas Jarrand <thomas.jarrand@gmail.com>
 */

namespace Stenope\Bundle\ReverseContent;

/**
 * Context to resolve a link to a content relative to the current one.
 */
class RelativeLinkContext extends Context
{
    private array $currentMetadata;
    private string $targetPath;

    public function __construct(array $currentMetadata, string $targetPath)
    {
        $this->currentMetadata = $currentMetadata;
        $this->targetPath = $targetPath;
    }

    public function getCurrentMetadata(): array
    {
        return $this->currentMetadata;
    }

    public function getTargetPath(): string
    {
        return $this->targetPath;
    }
}
