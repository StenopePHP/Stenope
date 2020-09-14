<?php

/*
 * This file is part of the "Tom32i/Content" bundle.
 *
 * @author Thomas Jarrand <thomas.jarrand@gmail.com>
 */

namespace Content;

use Content\Behaviour\ContentManagerAwareInterface;
use Content\Behaviour\ProcessorInterface;
use Content\Provider\ContentProviderInterface;
use Content\Serializer\Normalizer\SkippingInstantiatedObjectDenormalizer;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Symfony\Component\Serializer\Encoder\DecoderInterface;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Stopwatch\Stopwatch;

class ContentManager
{
    private DecoderInterface $decoder;
    private DenormalizerInterface $denormalizer;
    private PropertyAccessorInterface $propertyAccessor;

    /** @var iterable<ContentProviderInterface>|ContentProviderInterface[] */
    private iterable $providers;

    /** @var iterable<ProcessorInterface>|ProcessorInterface[] */
    private iterable $processors;

    private ?Stopwatch $stopwatch;

    /** @var array<string,object> */
    private array $cache = [];

    public function __construct(
        DecoderInterface $decoder,
        DenormalizerInterface $denormalizer,
        iterable $contentProviders,
        iterable $processors,
        ?PropertyAccessorInterface $propertyAccessor = null,
        ?Stopwatch $stopwatch = null
    ) {
        $this->decoder = $decoder;
        $this->denormalizer = $denormalizer;
        $this->propertyAccessor = $propertyAccessor ?? PropertyAccess::createPropertyAccessor();
        $this->providers = $contentProviders;
        $this->processors = $processors;
        $this->stopwatch = $stopwatch;
    }

    /**
     * List all content for the given type
     *
     * @param class-string<object>  $type   Model FQCN e.g. "App/Model/Article"
     * @param string|array|callable $sortBy String, array or callable
     *
     * @return object[] List of decoded contents
     */
    public function getContents(string $type, $sortBy = null): array
    {
        $contents = [];
        foreach ($this->getProviders($type) as $provider) {
            foreach ($provider->listContents() as $content) {
                $contents[] = $this->load($type, $content);
            }
        }

        if ($sorter = $this->getSortFunction($sortBy)) {
            \set_error_handler(static function (int $severity, string $message, ?string $file, ?int $line) use ($type): void {
                throw new \ErrorException(sprintf('There was a problem sorting %s: %s', $type, $message), $severity, $severity, $file, $line);
            });

            usort($contents, $sorter);

            \restore_error_handler();
        }

        return $contents;
    }

    /**
     * Fetch a specific content
     *
     * @param class-string<object> $type Model FQCN e.g. "App/Model/Article"
     * @param string               $id   Unique identifier (slug)
     *
     * @return object An object of the given type.
     */
    public function getContent(string $type, string $id): object
    {
        if ($this->stopwatch) {
            $event = $this->stopwatch->start('get_content', 'content');
        }

        foreach ($this->getProviders($type) as $provider) {
            if ($content = $provider->getContent($id)) {
                $loaded = $this->load($type, $content);

                if (isset($event)) {
                    $event->stop();
                }

                return $loaded;
            }
        }

        throw new \RuntimeException(sprintf('Content not found for type "%s" and id "%s".', $type, $id));
    }

    /**
     * @return iterable<ContentProviderInterface>|ContentProviderInterface[]
     */
    private function getProviders(string $type): iterable
    {
        if (is_countable($this->providers) && 0 === \count($this->providers)) {
            throw new \LogicException(sprintf('No content providers were configured. Did you forget to instantiate "%s" with the "$providers" argument, or to configure providers in the "content.providers" package config?', self::class));
        }

        $found = false;
        foreach ($this->providers as $provider) {
            if ($provider->supports($type)) {
                $found = true;
                yield $provider;
            }
        }

        if (!$found) {
            throw new \LogicException(sprintf('No provider found for type "%s"', $type));
        }
    }

    private function load(string $type, Content $content)
    {
        if ($data = $this->cache[$key = $content->getSlug()] ?? false) {
            return $data;
        }

        $this->initProcessors();

        $data = $this->decoder->decode($content->getRawContent(), $content->getFormat());

        // Apply processors to decoded data
        foreach ($this->processors as $processor) {
            $processor($data, $type, $content);
        }

        $data = $this->denormalizer->denormalize($data, $type, $content->getFormat(), [
            SkippingInstantiatedObjectDenormalizer::SKIP => true,
        ]);

        return $this->cache[$key] = $data;
    }

    private function getSortFunction($sortBy): ?callable
    {
        if (!$sortBy) {
            return null;
        }

        if (\is_string($sortBy)) {
            return $this->getSortFunction([$sortBy => true]);
        }

        if (\is_callable($sortBy)) {
            return $sortBy;
        }

        if (\is_array($sortBy)) {
            $key = array_keys($sortBy)[0];
            $asc = (bool) array_values($sortBy)[0];

            return function ($a, $b) use ($key, $asc) {
                $valueA = $this->propertyAccessor->getValue($a, $key);
                $valueB = $this->propertyAccessor->getValue($b, $key);

                return ($valueA <=> $valueB) * ($asc ? 1 : -1);
            };
        }

        throw new \LogicException('Unknown sorter');
    }

    public function supports(string $type): bool
    {
        foreach ($this->providers as $provider) {
            if ($provider->supports($type)) {
                return true;
            }
        }

        return false;
    }

    private function initProcessors(): void
    {
        static $managerInjected = false;
        // Lazy inject manager to processor on first need:
        if (!$managerInjected) {
            foreach ($this->processors as $processor) {
                if ($processor instanceof ContentManagerAwareInterface) {
                    $processor->setContentManager($this);
                }
            }
            $managerInjected = true;
        }
    }
}
