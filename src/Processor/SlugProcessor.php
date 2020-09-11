<?php

/*
 * This file is part of the "Tom32i/Content" bundle.
 *
 * @author Thomas Jarrand <thomas.jarrand@gmail.com>
 */

namespace Content\Processor;

use Content\Behaviour\ProcessorInterface;
use Content\Content;

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

    public function __invoke(array &$data, string $type, Content $content): void
    {
        if (isset($data[$this->property])) {
            // Slug already set.
            return;
        }

        $data[$this->property] = $content->getSlug();
    }
}
