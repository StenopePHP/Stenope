<?php

/*
 * This file is part of the "Tom32i/Content" bundle.
 *
 * @author Thomas Jarrand <thomas.jarrand@gmail.com>
 */

namespace Content\Behaviour;

use Content\Content;

/**
 * Processor interface
 */
interface ProcessorInterface
{
    /**
     * Apply modifications to decoded data before denormalization
     *
     * @param array   $data    The decoded data
     * @param string  $type    The model being processed (FQN)
     * @param Content $content The source content
     */
    public function __invoke(array &$data, string $type, Content $content): void;
}
