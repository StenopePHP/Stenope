<?php

/*
 * This file is part of the "StenopePHP/Stenope" bundle.
 *
 * @author Thomas Jarrand <thomas.jarrand@gmail.com>
 */

namespace Stenope\Bundle;

final class Content
{
    private string $slug;
    private string $type;
    private string $rawContent;
    private string $format;
    private ?\DateTime $lastModified;
    private ?\DateTime $createdAt;
    private array $metadata;

    public function __construct(
        string $slug,
        string $type,
        string $rawContent,
        string $format,
        ?\DateTime $lastModified = null,
        ?\DateTime $createdAt = null,
        array $metadata = []
    ) {
        $this->slug = $slug;
        $this->type = $type;
        $this->rawContent = $rawContent;
        $this->format = $format;
        $this->lastModified = $lastModified;
        $this->createdAt = $createdAt;
        $this->metadata = $metadata;
    }

    public function getSlug(): string
    {
        return $this->slug;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getRawContent(): string
    {
        return $this->rawContent;
    }

    public function getLastModified(): ?\DateTime
    {
        return $this->lastModified;
    }

    public function getCreatedAt(): ?\DateTime
    {
        return $this->createdAt;
    }

    public function getFormat(): string
    {
        return $this->format;
    }

    public function getMetadata(): array
    {
        return $this->metadata;
    }
}
