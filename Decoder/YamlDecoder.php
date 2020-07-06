<?php

/*
 * This file is part of the "Tom32i/Content" bundle.
 *
 * @author Thomas Jarrand <thomas.jarrand@gmail.com>
 */

namespace Content\Decoder;

use Content\Behaviour\ContentDecoderInterface;
use Symfony\Component\Serializer\Encoder\YamlEncoder;

/**
 * Parse YAML data
 */
class YamlDecoder extends YamlEncoder implements ContentDecoderInterface
{
}
