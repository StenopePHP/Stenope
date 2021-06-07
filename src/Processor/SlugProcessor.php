<?php

/*
 * This file is part of the "StenopePHP/Stenope" bundle.
 *
 * @author Thomas Jarrand <thomas.jarrand@gmail.com>
 */

namespace Stenope\Bundle\Processor;

use Stenope\Bundle\Behaviour\ProcessorInterface;
use Stenope\Bundle\Content;

/**
 * Set "slug" property from file name if not specified
 */
class SlugProcessor implements ProcessorInterface
{
    private string $property;

    public function __construct(string $property = 'slug')
    {
        $this->property = $property;
    }

    public function __invoke(array &$data, Content $content): void
    {
        if (isset($data[$this->property])) {
            // Slug already set.
            return;
        }

        $data[$this->property] = $content->getSlug();
    }
}
