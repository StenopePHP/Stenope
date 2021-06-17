<?php

/*
 * This file is part of the "StenopePHP/Stenope" bundle.
 *
 * @author Thomas Jarrand <thomas.jarrand@gmail.com>
 */

namespace Stenope\Bundle\Provider;

use Stenope\Bundle\Content;
use Stenope\Bundle\Provider\Factory\LocalFilesystemProviderFactory;
use Stenope\Bundle\ReverseContent\Context;
use Stenope\Bundle\ReverseContent\RelativeLinkContext;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\Glob;

class LocalFilesystemProvider implements ReversibleContentProviderInterface
{
    private string $supportedClass;
    private string $path;
    private ?string $depth;
    private array $excludes;

    /** @var string[] */
    private array $patterns;

    public function __construct(
        string $supportedClass,
        string $path,
        ?string $depth = null,
        array $excludes = [],
        array $patterns = ['*']
    ) {
        $this->supportedClass = $supportedClass;
        $this->path = $path;
        $this->depth = $depth;
        $this->excludes = $excludes;
        $this->patterns = $patterns;
    }

    /**
     * {@inheritdoc}
     */
    public function listContents(): iterable
    {
        foreach ($this->files() as $file) {
            yield $this->fromFile($file);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getContent(string $slug): ?Content
    {
        $files = $this->files()->filter(
            fn (\SplFileInfo $fileInfo) => trim("{$fileInfo->getRelativePath()}/{$fileInfo->getFilenameWithoutExtension()}", '/') === trim($slug, '/')
        );

        return ($file = current(iterator_to_array($files))) ? $this->fromFile($file) : null;
    }

    public function reverse(Context $context): ?Content
    {
        if (!$context instanceof RelativeLinkContext) {
            return null;
        }

        if (LocalFilesystemProviderFactory::TYPE !== ($context->getCurrentMetadata()['provider'] ?? null)) {
            // Cannot resolve relative to a non local filesystem content.
            return null;
        }

        $currentPath = $context->getCurrentMetadata()['path'] ?? null; // current Content path
        $target = $context->getTargetPath(); // relative path (to current) of the target we want to resolve
        $expectedPath = \dirname($currentPath) . '/' . $target;

        if (false === $expectedPath = realpath($expectedPath)) {
            return null;
        }

        foreach ($this->files() as $file) {
            if ($file->getRealPath() === $expectedPath) {
                return $this->fromFile($file);
            }
        }

        return null;
    }

    public function supports(string $className): bool
    {
        return $this->supportedClass === $className;
    }

    private function fromFile(\SplFileInfo $file): Content
    {
        return new Content(
            $this->getSlug($file),
            $this->supportedClass,
            file_get_contents($file->getPathname()),
            self::getFormat($file),
            new \DateTimeImmutable("@{$file->getMTime()}"),
            null,
            [
                'path' => $file->getRealPath(),
                'provider' => LocalFilesystemProviderFactory::TYPE,
            ]
        );
    }

    private function files(): Finder
    {
        if (!is_dir($this->path)) {
            throw new \LogicException(sprintf('Path "%s" is not a directory.', $this->path));
        }

        // Speedup filtering dirs by using `Finder::exclude()` when we identify a dir:
        $excludedDirs = array_filter($this->excludes, fn (string $pattern) => is_dir("{$this->path}/$pattern"));
        // Remaining files to exclude can either:
        // - be an exact file path with same name as an excluded dir
        // - or the ones from the excluded patterns, minus the previously directories matched.
        $excludedPatterns = array_diff(
            $this->excludes,
            array_filter($excludedDirs, fn (string $pattern) => !is_file("{$this->path}/$pattern"))
        );

        $finder = (new Finder())
            ->in($this->path)
            ->exclude($excludedDirs)
            ->notPath(array_map(fn ($exclude) => $this->convertPattern($exclude), $excludedPatterns))
            ->path(array_map(fn ($pattern) => $this->convertPattern($pattern), $this->patterns))
            ->sortByName()
        ;

        if ($this->depth) {
            $finder->depth($this->depth);
        }

        return $finder->files();
    }

    /**
     * Converts a pattern (which can either be a glob pattern or a simple path)
     * for usage with {@link Finder::path()} and {@link Finder::notPath()}
     */
    private function convertPattern(string $pattern): string
    {
        if (str_ends_with($pattern, '/')) {
            // If it ends with a "/", it was explicit as a directory,
            // the user is very likely to mean "anything inside":
            $pattern = "$pattern**";
        }

        return Glob::toRegex($pattern, true, false);
    }

    /**
     * Get the format of a file from its extension
     */
    private static function getFormat(\SplFileInfo $file): string
    {
        $ext = $file->getExtension();

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

    private function getSlug(\SplFileInfo $file): string
    {
        return substr($file->getRelativePathname(), 0, -(\strlen($file->getExtension()) + 1));
    }
}
