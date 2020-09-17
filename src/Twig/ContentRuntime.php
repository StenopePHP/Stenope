<?php

/*
 * This file is part of the "Tom32i/Content" bundle.
 *
 * @author Thomas Jarrand <thomas.jarrand@gmail.com>
 */

namespace Content\Twig;

use Content\ContentManager;
use Twig\Extension\RuntimeExtensionInterface;

class ContentRuntime implements RuntimeExtensionInterface
{
    private ContentManager $contentManager;

    public function __construct(ContentManager $contentManager)
    {
        $this->contentManager = $contentManager;
    }

    public function getContent(string $type, string $slug): object
    {
        return $this->contentManager->getContent($type, $slug);
    }

    /**
     * @return object[]
     */
    public function listContents(string $type, $sortBy, $filterBy): array
    {
        return $this->contentManager->getContents($type, $sortBy, $filterBy);
    }
}
