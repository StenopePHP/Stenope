<?php

/*
 * This file is part of the "StenopePHP/Stenope" bundle.
 *
 * @author Thomas Jarrand <thomas.jarrand@gmail.com>
 * @author Maxime Steinhausser <maxime.steinhausser@gmail.com>
 */

namespace Stenope\Bundle\Serializer\Normalizer;

use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

/**
 * Avoiding double-denormalization for already instantiated objects inside $data.
 *
 * @final
 */
class SkippingInstantiatedObjectDenormalizer implements DenormalizerInterface
{
    public const SKIP = 'skip_instantiated_object';

    public function denormalize($data, string $type, ?string $format = null, array $context = []): object
    {
        return $data;
    }

    public function supportsDenormalization($data, string $type, ?string $format = null, array $context = []): bool
    {
        return ($context[self::SKIP] ?? false) && \is_object($data);
    }

    public function getSupportedTypes(?string $format): array
    {
        return [
            'object' => true,
        ];
    }
}
