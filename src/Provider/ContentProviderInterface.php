<?php

/*
 * This file is part of the "Tom32i/Content" bundle.
 *
 * @author Thomas Jarrand <thomas.jarrand@gmail.com>
 */

namespace Content\Provider;

use Content\Content;

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
