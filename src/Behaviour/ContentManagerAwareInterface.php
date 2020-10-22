<?php

/*
 * This file is part of the "StenopePHP/Stenope" bundle.
 *
 * @author Thomas Jarrand <thomas.jarrand@gmail.com>
 */

namespace Stenope\Behaviour;

use Stenope\ContentManager;

interface ContentManagerAwareInterface
{
    /**
     * Sets the owning ContentManager object.
     */
    public function setContentManager(ContentManager $contentManager);
}
