<?php

/*
 * This file is part of the "Tom32i/Content" bundle.
 *
 * @author Thomas Jarrand <thomas.jarrand@gmail.com>
 */

namespace Content\Behaviour;

use Content\ContentManager;

interface ContentManagerAwareInterface
{
    /**
     * Sets the owning ContentManager object.
     */
    public function setContentManager(ContentManager $contentManager);
}
