<?php

/*
 * This file is part of the "StenopePHP/Stenope" bundle.
 *
 * @author Thomas Jarrand <thomas.jarrand@gmail.com>
 */

namespace Stenope\Bundle\Provider;

use Stenope\Bundle\Content;

interface ContentProviderInterface
{
    /**
     * @return iterable<Content>|Content[]
     */
    public function listContents(): iterable;

    public function getContent(string $slug): ?Content;

    /**
     * @param class-string<object> $className
     */
    public function supports(string $className): bool;
}
