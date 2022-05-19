<?php

/*
 * This file is part of the "StenopePHP/Stenope" bundle.
 *
 * @author Thomas Jarrand <thomas.jarrand@gmail.com>
 */

namespace Stenope\Bundle\Serializer\Normalizer;

use Composer\InstalledVersions;
use Symfony\Component\Serializer\Normalizer\ContextAwareDenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

if (-1 === version_compare(InstalledVersions::getVersion('symfony/serializer'), '6.1.0')) {
    /**
     * Avoiding double-denormalization for already instantiated objects inside $data.
     *
     * @final
     */
    class SkippingInstantiatedObjectDenormalizer implements ContextAwareDenormalizerInterface
    {
        public const SKIP = 'skip_instantiated_object';

        public function denormalize($data, string $type, string $format = null, array $context = []): object
        {
            return $data;
        }

        public function supportsDenormalization($data, string $type, string $format = null, array $context = []): bool
        {
            return (bool) ($context[self::SKIP] ?? false) && \is_object($data);
        }
    }
} else {
    /**
     * Avoiding double-denormalization for already instantiated objects inside $data.
     *
     * @final
     */
    class SkippingInstantiatedObjectDenormalizer implements DenormalizerInterface
    {
        public const SKIP = 'skip_instantiated_object';

        public function denormalize($data, string $type, string $format = null, array $context = []): object
        {
            return $data;
        }

        public function supportsDenormalization($data, string $type, string $format = null, array $context = []): bool
        {
            return (bool) ($context[self::SKIP] ?? false) && \is_object($data);
        }
    }
}
