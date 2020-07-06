<?php

/*
 * This file is part of the "Tom32i/Content" bundle.
 *
 * @author Thomas Jarrand <thomas.jarrand@gmail.com>
 */

namespace Content\Behaviour;

interface ContentProviderInterface
{
    public function supports(string $className): bool;
}
