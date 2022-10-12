<?php

/*
 * This file is part of the "StenopePHP/Stenope" bundle.
 *
 * @author Thomas Jarrand <thomas.jarrand@gmail.com>
 */

namespace Stenope\Bundle;

use Stenope\Bundle\Exception\RuntimeException;

class ContentBag
{
    private array $contents = [];
    private bool $locked = false;
    private string $type;

    /** @var callable Sorting function */
    private $sorter = null;

    /** @var callable Filter function */
    private $filter = null;

    public function __construct(string $type, ?callable $sorter = null, ?callable $filter = null)
    {
        $this->type = $type;
        $this->sorter = $sorter;
        $this->filter = $filter;
    }

    public function add(string $identifier, object $object): void
    {
        if ($this->locked) {
            throw new RuntimeException('Contents have already been filtered and sorted.', 1);
        }

        if (isset($this->contents[$identifier])) {
            throw new RuntimeException(sprintf(
                'Found multiple contents of type "%s" with the same "%s" identifier.',
                $this->type,
                $identifier
            ));
        }

        $this->contents[$identifier] = $object;
    }

    public function getContents(): array
    {
        try {
            $this->applyFilter();
        } catch (\Throwable $exception) {
            throw new RuntimeException(sprintf('There was a problem filtering %s.', $this->type), 0, $exception);
        }

        try {
            $this->applySort();
        } catch (\Throwable $exception) {
            throw new RuntimeException(sprintf('There was a problem sorting %s.', $this->type), 0, $exception);
        }

        $this->locked = true;

        return $this->contents;
    }

    private function applyFilter(): void
    {
        if ($this->filter === null) {
            return;
        }

        $this->contents = array_filter($this->contents, $this->filter);
    }

    private function applySort(): void
    {
        if ($this->sorter === null) {
            return;
        }

        set_error_handler(static function (int $severity, string $message, ?string $file, ?int $line): void {
            throw new \ErrorException($message, $severity, $severity, $file, $line);
        });

        uasort($this->contents, $this->sorter);

        restore_error_handler();
    }
}
