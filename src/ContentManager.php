<?php

/*
 * This file is part of the "StenopePHP/Stenope" bundle.
 *
 * @author Thomas Jarrand <thomas.jarrand@gmail.com>
 */

namespace Stenope\Bundle;

use Stenope\Bundle\Behaviour\ContentManagerAwareInterface;
use Stenope\Bundle\Behaviour\HtmlCrawlerManagerInterface;
use Stenope\Bundle\Behaviour\ProcessorInterface;
use Stenope\Bundle\Exception\ContentNotFoundException;
use Stenope\Bundle\Exception\RuntimeException;
use Stenope\Bundle\ExpressionLanguage\ExpressionLanguage;
use Stenope\Bundle\Provider\ContentProviderInterface;
use Stenope\Bundle\Provider\ReversibleContentProviderInterface;
use Stenope\Bundle\ReverseContent\Context;
use Stenope\Bundle\Serializer\Normalizer\SkippingInstantiatedObjectDenormalizer;
use Symfony\Component\ExpressionLanguage\Expression;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage as BaseExpressionLanguage;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Symfony\Component\Serializer\Encoder\DecoderInterface;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Stopwatch\Stopwatch;

class ContentManager implements ContentManagerInterface
{
    private DecoderInterface $decoder;
    private DenormalizerInterface $denormalizer;
    private PropertyAccessorInterface $propertyAccessor;
    private HtmlCrawlerManagerInterface $crawlers;

    /** @var iterable<ContentProviderInterface>|ContentProviderInterface[] */
    private iterable $providers;

    /** @var iterable<ProcessorInterface>|ProcessorInterface[] */
    private iterable $processors;

    private ?ExpressionLanguage $expressionLanguage;

    private ?Stopwatch $stopwatch;

    /** @var array<string,object> */
    private array $cache = [];

    /** @var array<string,object> */
    private array $reversedCache = [];

    private bool $managerInjected = false;

    private ?ContentManagerInterface $contentManager;

    public function __construct(
        DecoderInterface $decoder,
        DenormalizerInterface $denormalizer,
        HtmlCrawlerManagerInterface $crawlers,
        iterable $contentProviders,
        iterable $processors,
        ?PropertyAccessorInterface $propertyAccessor = null,
        ?ExpressionLanguage $expressionLanguage = null,
        ?Stopwatch $stopwatch = null
    ) {
        $this->decoder = $decoder;
        $this->denormalizer = $denormalizer;
        $this->crawlers = $crawlers;
        $this->propertyAccessor = $propertyAccessor ?? PropertyAccess::createPropertyAccessor();
        $this->providers = $contentProviders;
        $this->processors = $processors;
        $this->stopwatch = $stopwatch;

        if (!$expressionLanguage && class_exists(BaseExpressionLanguage::class)) {
            $expressionLanguage = new ExpressionLanguage();
        }
        $this->expressionLanguage = $expressionLanguage;
    }

    /**
     * {@inheritdoc}
     */
    public function getContents(string $type, $sortBy = null, $filterBy = null): array
    {
        $contents = new ContentBag($type, $this->getSortFunction($sortBy), $this->getFilterFunction($filterBy));

        foreach ($this->getProviders($type) as $provider) {
            foreach ($provider->listContents() as $content) {
                if ($content->isList()) {
                    foreach ($this->load($content) as $index => $object) {
                        $contents->add("{$content->getSlug()}[{$index}]", $object);
                    }
                } else {
                    $contents->add($content->getSlug(), $this->load($content));
                }
            }
        }

        return $contents->getContents();
    }

    /**
     * {@inheritdoc}
     */
    public function getContent(string $type, string $id, ?int $index = null): object
    {
        if ($this->stopwatch) {
            $event = $this->stopwatch->start('get_content', 'stenope');
        }

        foreach ($this->getProviders($type) as $provider) {
            if ($content = $provider->getContent($id)) {
                $loaded = $this->load($content);

                if (isset($event)) {
                    $event->stop();
                }

                if ($content->isList()) {
                    if ($index === null) {
                        throw new RuntimeException("No index provided for list content \"$id\".", 1);
                    }

                    return $loaded[$index];
                }

                return $loaded;
            }
        }

        throw new ContentNotFoundException($type, $id);
    }

    /**
     * {@inheritdoc}
     */
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

    private function load(Content $content)
    {
        if ($data = $this->cache[$key = "{$content->getType()}:{$content->getSlug()}"] ?? false) {
            return $data;
        }

        $this->initProcessors();

        $data = $this->decoder->decode($content->getRawContent(), $content->getFormat());

        // Apply processors to decoded data
        foreach ($this->processors as $processor) {
            $processor($data, $content);
        }

        $this->crawlers->saveAll($content, $data);

        $data = $this->denormalizer->denormalize($data, $content->getType() . ($content->isList() ? '[]' : ''), $content->getFormat(), [
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

        if ($filterBy instanceof Expression || \is_string($filterBy)) {
            return fn ($data) => $this->expressionLanguage->evaluate($filterBy, [
                'data' => $data,
                'd' => $data,
                '_' => $data,
            ]);
        }

        if (\is_callable($filterBy)) {
            return $filterBy;
        }

        if (\is_array($filterBy)) {
            return function ($item) use ($filterBy) {
                foreach ($filterBy as $key => $expectedValue) {
                    $value = $this->propertyAccessor->getValue($item, $key);

                    if (\is_callable($expectedValue)) {
                        // if the expected value is a callable, call it with the current content property value:
                        if ($expectedValue($value)) {
                            continue;
                        }

                        return false;
                    }

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
                    $processor->setContentManager($this->contentManager ?? $this);
                }
            }
            $this->managerInjected = true;
        }
    }

    /**
     * Set the actual content manager instance to inject in processors.
     * Useful whenever this content manager is decorated in order for the processor to use the decorating one.
     */
    public function setContentManager(ContentManagerInterface $contentManager): void
    {
        if ($contentManager === $this) {
            return;
        }

        $this->contentManager = $contentManager;
    }
}
