<?php

/*
 * This file is part of the "StenopePHP/Stenope" bundle.
 *
 * @author Thomas Jarrand <thomas.jarrand@gmail.com>
 */

namespace Stenope\Bundle\Provider;

use Stenope\Bundle\Content;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\Glob;
use Symfony\Component\Finder\SplFileInfo;

class LocalFilesystemProvider implements ContentProviderInterface
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

    public function listContents(): iterable
    {
        foreach ($this->files() as $file) {
            yield $this->fromFile($file);
        }
    }

    public function getContent(string $slug): ?Content
    {
        $files = $this->files()->filter(
            fn (SplFileInfo $fileInfo) => trim("{$fileInfo->getRelativePath()}/{$fileInfo->getFilenameWithoutExtension()}", '/') === trim($slug, '/')
        );

        return ($file = current(iterator_to_array($files))) ? $this->fromFile($file) : null;
    }

    public function supports(string $className): bool
    {
        return $this->supportedClass === $className;
    }

    private function fromFile(SplFileInfo $file): Content
    {
        return new Content(
            $this->getSlug($file),
            file_get_contents($file->getPathname()),
            self::getFormat($file),
            new \DateTime("@{$file->getMTime()}"),
            null,
        );
    }

    private function files(): Finder
    {
        if (!is_dir($this->path)) {
            throw new \LogicException(sprintf('Path "%s" is not a directory.', $this->path));
        }

        $finder = (new Finder())
            ->in($this->path)
            ->notPath(array_map(fn ($exclude) => Glob::toRegex($exclude, true, false), $this->excludes))
            ->path(array_map(fn ($pattern) => Glob::toRegex($pattern, true, false), $this->patterns))
        ;

        if ($this->depth) {
            $finder->depth($this->depth);
        }

        return $finder->files();
    }

    /**
     * Get the format of a file from its extension
     */
    private static function getFormat(SplFileInfo $file): string
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

    private function getSlug(SplFileInfo $file): string
    {
        return substr($file->getRelativePathname(), 0, -(\strlen($file->getExtension()) + 1));
    }
}
