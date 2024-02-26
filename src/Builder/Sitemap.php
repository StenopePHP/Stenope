<?php

/*
 * This file is part of the "StenopePHP/Stenope" bundle.
 *
 * @author Thomas Jarrand <thomas.jarrand@gmail.com>
 * @author Maxime Steinhausser <maxime.steinhausser@gmail.com>
 */

namespace Stenope\Bundle\Builder;

/**
 * @phpstan-implements \Iterator<string,string>
 *
 * @final
 */
class Sitemap implements \Iterator, \Countable
{
    /**
     * Mapped URLs
     */
    private array $urls = [];

    private int $position = 0;

    /**
     * Add location
     *
     * @param string    $location     The URL
     * @param \DateTime $lastModified Date of last modification
     * @param int       $priority     Location priority
     */
    public function add(string $location, ?\DateTime $lastModified = null, ?int $priority = null, ?string $frequency = null): void
    {
        $url = ['location' => $location];

        if ($priority === null && empty($this->urls)) {
            $priority = 0;
        }

        if ($lastModified) {
            $url['lastModified'] = $lastModified;
        }

        if ($priority !== null) {
            $url['priority'] = $priority;
        }

        if ($frequency) {
            $url['frequency'] = $frequency;
        }

        $this->urls[$location] = $url;
    }

    public function rewind(): void
    {
        $this->position = 0;
    }

    #[\ReturnTypeWillChange]
    public function current(): mixed
    {
        return $this->urls[array_keys($this->urls)[$this->position]];
    }

    #[\ReturnTypeWillChange]
    public function key(): mixed
    {
        return array_keys($this->urls)[$this->position];
    }

    public function next(): void
    {
        ++$this->position;
    }

    public function valid(): bool
    {
        return isset(array_keys($this->urls)[$this->position]);
    }

    public function count(): int
    {
        return \count($this->urls);
    }
}
