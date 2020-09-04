<?php

/*
 * This file is part of the "Tom32i/Content" bundle.
 *
 * @author Thomas Jarrand <thomas.jarrand@gmail.com>
 */

namespace Content;

use Content\Behaviour\ContentProviderInterface;
use Content\Behaviour\PropertyHandlerInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Symfony\Component\Serializer\SerializerInterface;

class ContentManager
{
    private string $path;
    private SerializerInterface $serializer;
    private FileSystem $files;
    private PropertyAccessorInterface $propertyAccessor;

    /** @var iterable<ContentProviderInterface> */
    private iterable $providers;

    /** @var iterable<string, PropertyHandlerInterface> indexed by property name */
    private iterable $handlers;

    private array $cache;

    public function __construct(
        string $path,
        SerializerInterface $serializer,
        iterable $propertyHandlers,
        iterable $contentProviders,
        ?PropertyAccessorInterface $propertyAccessor = null
    ) {
        $this->path = rtrim($path, '/');
        $this->serializer = $serializer;
        $this->propertyAccessor = $propertyAccessor ?? PropertyAccess::createPropertyAccessor();
        $this->files = new FileSystem();
        $this->providers = $contentProviders;
        $this->handlers = $propertyHandlers;
        $this->cache = [
            'files' => [],
            'contents' => [],
        ];
    }

    /**
     * List all content for the given type
     *
     * @param string $type   Model e.g. "App/Model/Article"
     * @param mixed  $sortBy String, array or callable
     *
     * @return array List of contents
     */
    public function getContents(string $type, $sortBy = null): array
    {
        $contents = [];
        $provider = $this->getProvider($type);

        foreach ($this->listFiles($provider) as $file) {
            $contents[] = $this->load($type, $file);
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
     * @param string $type Model  e.g. "App/Model/Article"
     * @param string $id   Unique identifier (name of the file)
     *
     * @return mixed An object of the given type.
     */
    public function getContent(string $type, string $id)
    {
        $provider = $this->getProvider($type);
        $files = $this->listFiles($provider)->name($id . '.*');

        if (!$files->hasResults()) {
            throw new \Exception(sprintf('Content not found for type "%s" and id "%s".', $type, $id));
        }

        return $this->load($type, current(\iterator_to_array($files)));
    }

    private function getProvider(string $type): ContentProviderInterface
    {
        foreach ($this->providers as $provider) {
            if ($provider->supports($type)) {
                return $provider;
            }
        }

        throw new \Exception('No provider found for type: ' . $type);
    }

    /**
     * Get the format of a file from its extension
     *
     * @param SplFileInfo $file The file
     *
     * @return string The format
     */
    private static function getFormat(SplFileInfo $file): string
    {
        $name = $file->getRelativePathname();
        $ext = substr($name, strrpos($name, '.') + 1);

        switch ($ext) {
            case 'md':
                return 'markdown';

            case 'yml':
            case 'yaml':
                return 'yaml';

            default:
                return $ext;
        }
    }

    /**
     * Get the name of a file
     *
     * @param SplFileInfo $file The file
     *
     * @return string The name
     */
    private static function getName(SplFileInfo $file): string
    {
        $name = $file->getRelativePathname();

        return substr($name, 0, strrpos($name, '.'));
    }

    private function listFiles(ContentProviderInterface $provider): Finder
    {
        $path = sprintf('%s/%s', $this->path, $provider->getDirectory());

        if (!isset($this->cache['files'][$path])) {
            if (!$this->files->exists($path)) {
                throw new \Exception(sprintf(
                    'Content directory not found. Path "%s" does not exist.',
                    $this->path
                ));
            }

            $finder = new Finder();

            $this->cache['files'][$path] = $finder->files()->in($path);
        }

        return clone $this->cache['files'][$path];
    }

    private function load(string $type, SplFileInfo $file)
    {
        $path = $file->getPathName();

        if (!isset($this->cache['contents'][$path])) {
            $format = static::getFormat($file);
            $data = $this->serializer->decode($file->getContents(), $format);

            foreach ($this->handlers as $property => $handler) {
                $value = $data[$property] ?? null;

                if ($handler->isSupported($value)) {
                    $data[$property] = $handler->handle($value, ['file' => $file, 'data' => $data]);
                }
            }

            $data = $this->serializer->denormalize($data, $type, $format);

            $this->cache['contents'][$path] = $data;
        }

        return $this->cache['contents'][$path];
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

        throw new \Exception('Unknown sorter');
    }

    /**
     * Get index of the given content for content lists
     *
     * @param array|object $content
     *
     * @return string The string index (by default, the file name)
     */
    private function getIndex(SplFileInfo $file, $content, string $key = null)
    {
        if ($key === null || !$this->propertyAccessor->isReadable($content, $key)) {
            return static::getName($file);
        }

        $index = $this->propertyAccessor->getValue($content, $key);

        if ($index instanceof \DateTime) {
            return $index->format('U');
        }

        return (string) $index;
    }
}
