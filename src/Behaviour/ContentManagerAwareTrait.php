<?php

/*
 * This file is part of the "StenopePHP/Stenope" bundle.
 *
 * @author Thomas Jarrand <thomas.jarrand@gmail.com>
 */

namespace Stenope\Behaviour;

use Stenope\ContentManager;

trait ContentManagerAwareTrait
{
    private ContentManager $contentManager;

    public function setContentManager(ContentManager $contentManager): void
    {
        $this->contentManager = $contentManager;
    }
}
