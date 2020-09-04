<?php

/*
 * This file is part of the "Tom32i/Content" bundle.
 *
 * @author Thomas Jarrand <thomas.jarrand@gmail.com>
 */

namespace Content\Behaviour;

/**
 * Property Handler interface
 */
interface PropertyHandlerInterface
{
    /**
     * Is data supported?
     *
     * @param mixed $value The property value
     */
    public function isSupported($value): bool;

    /**
     * Handle property
     *
     * @param mixed $value   The property value
     * @param array $context The context of parsing process
     */
    public function handle($value, array $context);
}
