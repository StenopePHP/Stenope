<?php

/*
 * This file is part of the "StenopePHP/Stenope" bundle.
 *
 * @author Thomas Jarrand <thomas.jarrand@gmail.com>
 */

namespace Stenope;

final class Content
{
    private string $slug;
    private string $rawContent;
    private string $format;
    private ?\DateTime $lastModified;
    private ?\DateTime $createdAt;

    public function __construct(
        string $slug,
        string $rawContent,
        string $format,
        ?\DateTime $lastModified = null,
        ?\DateTime $createdAt = null
    ) {
        $this->slug = $slug;
        $this->rawContent = $rawContent;
        $this->format = $format;
        $this->lastModified = $lastModified;
        $this->createdAt = $createdAt;
    }

    public function getSlug(): string
    {
        return $this->slug;
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
}
