<?php

/*
 * This file is part of the "StenopePHP/Stenope" bundle.
 *
 * @author Thomas Jarrand <thomas.jarrand@gmail.com>
 */

namespace Stenope\Bundle\Attribute;

#[\Attribute(\Attribute::TARGET_CLASS | \Attribute::IS_REPEATABLE)]
class SuggestedDebugQuery
{
    public array $orders;

    public function __construct(
        public string $description,
        public ?string $filters = null,
        array|string|null $orders = null
    ) {
        $this->orders = (array) $orders;
    }
}
