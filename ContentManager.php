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
use Symfony\Component\Serializer\Serializer;

class ContentManager
{
    private $path;
    private $serializer;
    private $files;
    private $propertyAccessor;
    private $providers;
    private $handlers;
    private $cache;

    public function __construct(string $path, array $denormalizers = [], array $decoders = [])
    {
        $this->path = rtrim($path, '/');
        $this->serializer = new Serializer($denormalizers, $decoders);
        $this->files = new FileSystem();
        $this->propertyAccessor = PropertyAccess::createPropertyAccessor();
        $this->providers = [];
        $this->handlers = [];
        $this->cache = [
            'files' => [],
            'contents' => [],
        ];
    }

    public function addProvider(ContentProviderInterface $provider): void
    {
        $this->providers[] = $provider;
    }

    public function addPropertyHandler(string $property, PropertyHandlerInterface $handler): void
    {
        $this->handlers[$property] = $handler;
    }

    public function getContents(string $type, $sortBy = null): array
    {
        $contents = [];
        $provider = $this->getProvider($type);

        foreach ($this->listFiles($provider) as $file) {
            $contents[] = $this->load($provider, $type, $file);
        }

        if ($sorter = $this->getSortFunction($sortBy)) {
            usort($contents, $sorter);
        }

        return $contents;
    }

    public function getContent(string $type, string $id)
    {
        $provider = $this->getProvider($type);
        $files = $this->listFiles($provider)->name($id . '.*');

        if (!$files->hasResults()) {
            throw new \Exception(sprintf('Content not found for type "%s" and id "%s".', $type, $id));
        }

        return $this->load($provider, $type, current(\iterator_to_array($files)));
    }

    public function addContentProvider(ContentProviderInterface $provider): void
    {
        $this->providers[] = $provider;
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

    private function load(ContentProviderInterface $provider, string $type, SplFileInfo $file)
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

    public function sortContent(array $contents, callable $sorter): void
    {
        if (!$sorter) {
            return;
        }

        usort($contents, $sorter);
    }

    private function getSortFunction($sortBy): callable
    {
        if (!$sortBy) {
            return null;
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

                if ($valueA == $valueB) {
                    return 0;
                }

                return ($valueA > $valueB) === $asc ? 1 : -1;
            };
        }

        if (\is_string($sortBy)) {
            return $this->getSortFunction([$sortBy => true]);
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
