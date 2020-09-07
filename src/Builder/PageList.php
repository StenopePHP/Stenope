<?php

/*
 * This file is part of the "Tom32i/Content" bundle.
 *
 * @author Thomas Jarrand <thomas.jarrand@gmail.com>
 */

namespace Content\Builder;

/**
 * Page List
 */
class PageList
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

    private function getQueue(): array
    {
        return array_keys(array_filter($this->urls));
    }
}
