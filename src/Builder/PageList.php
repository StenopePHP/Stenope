<?php

/*
 * This file is part of the "StenopePHP/Stenope" bundle.
 *
 * @author Thomas Jarrand <thomas.jarrand@gmail.com>
 */

namespace Stenope\Bundle\Builder;

/**
 * Page List
 */
class PageList implements \Countable
{
    /** @var array<string,bool> */
    private array $urls = [];

    public function add(string $url): void
    {
        if (!isset($this->urls[$url])) {
            $this->urls[$url] = true;
        }
    }

    public function markAsDone(string $url): void
    {
        if (isset($this->urls[$url])) {
            $this->urls[$url] = false;
        }
    }

    public function getNext(): string
    {
        return current($this->getQueue());
    }

    public function count(): int
    {
        return \count($this->urls);
    }

    private function getQueue(): array
    {
        return array_keys(array_filter($this->urls));
    }
}
