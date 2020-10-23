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
 * Set a "LastModified" property based on file date
 */
class LastModifiedProcessor implements ProcessorInterface
{
    private string $property;

    public function __construct(string $property = 'lastModified')
    {
        $this->property = $property;
    }

    public function __invoke(array &$data, string $type, Content $content): void
    {
        if (isset($data[$this->property])) {
            // Last modified already set.
            return;
        }

        $data[$this->property] = $content->getLastModified() ? $content->getLastModified()->format(\DateTimeInterface::RFC3339) : null;
    }
}
