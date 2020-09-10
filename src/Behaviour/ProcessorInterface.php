<?php

/*
 * This file is part of the "Tom32i/Content" bundle.
 *
 * @author Thomas Jarrand <thomas.jarrand@gmail.com>
 */

namespace Content\Behaviour;

/**
 * Processor interface
 */
interface ProcessorInterface
{
    /**
     * Apply modifications to decoded data before denormalization
     *
     * @param array $data    The decoded data
     * @param array $context The context of parsing process
     */
    public function __invoke(array &$data, array $context): void;
}
