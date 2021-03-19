<?php

/*
 * This file is part of the "StenopePHP/Stenope" bundle.
 *
 * @author Thomas Jarrand <thomas.jarrand@gmail.com>
 */

namespace Stenope\Bundle;

use Stenope\Bundle\Behaviour\ContentManagerAwareInterface;
use Stenope\Bundle\Behaviour\ProcessorInterface;
use Stenope\Bundle\Exception\ContentNotFoundException;
use Stenope\Bundle\Provider\ContentProviderInterface;
use Stenope\Bundle\Provider\ReversibleContentProviderInterface;
use Stenope\Bundle\ReverseContent\Context;
use Stenope\Bundle\Serializer\Normalizer\SkippingInstantiatedObjectDenormalizer;
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

    /** @var array<string,object> */
    private array $reversedCache = [];

    private bool $managerInjected = false;

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
     * @template T
     *
     * @param class-string<T>       $type     Model FQCN e.g. "App/Model/Article"
     * @param string|array|callable $sortBy   String, array or callable
     * @param string|array|callable $filterBy String, array or callable
     *
     * @return array<T> List of decoded contents
     */
    public function getContents(string $type, $sortBy = null, $filterBy = null): array
    {
        $contents = [];

        foreach ($this->getProviders($type) as $provider) {
            foreach ($provider->listContents() as $content) {
                $contents[] = $this->load($type, $content);
            }
        }

        try {
            $this->filterBy($contents, $filterBy);
        } catch (\Throwable $exception) {
            throw new \RuntimeException(sprintf('There was a problem filtering %s.', $type), 0, $exception);
        }

        try {
            $this->sortBy($contents, $sortBy);
        } catch (\Throwable $exception) {
            throw new \RuntimeException(sprintf('There was a problem sorting %s.', $type), 0, $exception);
        }

        return $contents;
    }

    public function filterBy(array &$contents, $filterBy = null): void
    {
        if ($filter = $this->getFilterFunction($filterBy)) {
            $contents = array_filter($contents, $filter);
        }
    }

    public function sortBy(array &$contents, $sortBy = null): void
    {
        if ($sorter = $this->getSortFunction($sortBy)) {
            \set_error_handler(static function (int $severity, string $message, ?string $file, ?int $line): void {
                throw new \ErrorException($message, $severity, $severity, $file, $line);
            });

            usort($contents, $sorter);

            \restore_error_handler();
        }
    }

    /**
     * Fetch a specific content
     *
     * @template T
     *
     * @param class-string<T> $type Model FQCN e.g. "App/Model/Article"
     * @param string          $id   Unique identifier (slug)
     *
     * @return T An object of the given type.
     */
    public function getContent(string $type, string $id): object
    {
        if ($this->stopwatch) {
            $event = $this->stopwatch->start('get_content', 'stenope');
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

        throw new ContentNotFoundException($type, $id);
    }

    public function reverseContent(Context $context): ?Content
    {
        $key = md5(serialize($context));
        if (\array_key_exists($key, $this->reversedCache)) {
            return $this->reversedCache[$key];
        }

        if ($this->stopwatch) {
            $event = $this->stopwatch->start('reverse_content', 'stenope');
        }

        try {
            foreach ($this->providers as $provider) {
                if (!$provider instanceof ReversibleContentProviderInterface) {
                    continue;
                }

                if ($result = $provider->reverse($context)) {
                    return $this->reversedCache[$key] = $result;
                }
            }
        } finally {
            if (isset($event)) {
                $event->stop();
            }
        }

        return $this->reversedCache[$key] = null;
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
        if ($data = $this->cache[$key = "$type:{$content->getSlug()}"] ?? false) {
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
            return function ($a, $b) use ($sortBy) {
                foreach ($sortBy as $key => $value) {
                    $asc = (bool) $value;
                    $valueA = $this->propertyAccessor->getValue($a, $key);
                    $valueB = $this->propertyAccessor->getValue($b, $key);

                    if ($valueA === $valueB) {
                        continue;
                    }

                    return ($valueA <=> $valueB) * ($asc ? 1 : -1);
                }

                return 0;
            };
        }

        throw new \LogicException(sprintf('Unknown sorter "%s"', $sortBy));
    }

    private function getFilterFunction($filterBy): ?callable
    {
        if (!$filterBy) {
            return null;
        }

        if (\is_string($filterBy)) {
            return $this->getFilterFunction([$filterBy => true]);
        }

        if (\is_callable($filterBy)) {
            return $filterBy;
        }

        if (\is_array($filterBy)) {
            return function ($item) use ($filterBy) {
                foreach ($filterBy as $key => $expectedValue) {
                    $value = $this->propertyAccessor->getValue($item, $key);

                    if ($value == $expectedValue) {
                        continue;
                    }

                    return false;
                }

                return true;
            };
        }

        throw new \LogicException(sprintf('Unknown filter "%s"', $filterBy));
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
        // Lazy inject manager to processor on first need:
        if (!$this->managerInjected) {
            foreach ($this->processors as $processor) {
                if ($processor instanceof ContentManagerAwareInterface) {
                    $processor->setContentManager($this);
                }
            }
            $this->managerInjected = true;
        }
    }
}
