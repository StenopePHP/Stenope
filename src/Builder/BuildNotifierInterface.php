<?php

/*
 * This file is part of the "Tom32i/Content" bundle.
 *
 * @author Thomas Jarrand <thomas.jarrand@gmail.com>
 */

namespace Content\Builder;

interface BuildNotifierInterface
{
    public function notify(
        ?string $stepName = null,
        ?int $advance = null,
        ?int $maxStep = null,
        ?string $message = null
    ): void;
}
