<?php

/*
 * This file is part of the "Tom32i/Content" bundle.
 *
 * @author Thomas Jarrand <thomas.jarrand@gmail.com>
 */

namespace Content\Behaviour;

use Content\ContentManager;

trait ContentManagerAwareTrait
{
    private ContentManager $contentManager;

    public function setContentManager(ContentManager $contentManager): void
    {
        $this->contentManager = $contentManager;
    }
}
