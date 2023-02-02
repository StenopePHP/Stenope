<?php

/*
 * This file is part of the "StenopePHP/Stenope" bundle.
 *
 * @author Thomas Jarrand <thomas.jarrand@gmail.com>
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
     *
     * @var array
     */
    private $urls = [];

    /**
     * @var int
     */
    private $position = 0;

    /**
     * Add location
     *
     * @param string    $location     The URL
     * @param \DateTime $lastModified Date of last modification
     * @param int       $priority     Location priority
     * @param string    $frequency
     */
    public function add(string $location, \DateTime $lastModified = null, int $priority = null, string $frequency = null): void
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

    /**
     * {@inheritdoc}
     */
    public function rewind(): void
    {
        $this->position = 0;
    }

    /**
     * {@inheritdoc}
     *
     * @return mixed
     */
    #[\ReturnTypeWillChange]
    public function current()
    {
        return $this->urls[array_keys($this->urls)[$this->position]];
    }

    /**
     * {@inheritdoc}
     *
     * @return mixed
     */
    #[\ReturnTypeWillChange]
    public function key()
    {
        return array_keys($this->urls)[$this->position];
    }

    /**
     * {@inheritdoc}
     */
    public function next(): void
    {
        ++$this->position;
    }

    /**
     * {@inheritdoc}
     */
    public function valid(): bool
    {
        return isset(array_keys($this->urls)[$this->position]);
    }

    /**
     * {@inheritdoc}
     */
    public function count(): int
    {
        return \count($this->urls);
    }
}
