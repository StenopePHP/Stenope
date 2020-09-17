<?php

/*
 * This file is part of the "Tom32i/Content" bundle.
 *
 * @author Thomas Jarrand <thomas.jarrand@gmail.com>
 */

namespace Content\HttpFoundation;

use Symfony\Component\HttpFoundation\Request;

/**
 * A request with the base url explicitly provided.
 */
class ContentRequest extends Request
{
    public function withBaseUrl(string $baseUrl)
    {
        $this->baseUrl = $baseUrl;

        return $this;
    }
}
