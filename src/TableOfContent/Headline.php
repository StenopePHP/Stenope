<?php

/*
 * This file is part of the "StenopePHP/Stenope" bundle.
 *
 * @author Thomas Jarrand <thomas.jarrand@gmail.com>
 */

namespace Stenope\Bundle\TableOfContent;

class Headline implements \JsonSerializable
{
    public int $level;
    public ?string $content;
    private ?string $id;
    /** @var Headline[] */
    public array $children = [];
    public ?Headline $parent = null;

    public function __construct(int $level, ?string $id, ?string $content, array $children = [])
    {
        $this->level = $level;
        $this->content = $content;
        $this->id = $id;

        foreach ($children as $child) {
            $this->addChild($child);
        }
    }

    public function addChild(Headline $headline): void
    {
        $this->children[] = $headline;
        $headline->setParent($this);
    }

    public function setParent(Headline $parent): void
    {
        $this->parent = $parent;
    }

    public function hasChildren(): bool
    {
        return \count($this->children) > 0;
    }

    public function getChildren(): array
    {
        return $this->children;
    }

    public function getId(): ?string
    {
        return $this->id;
    }

    public function getContent(): ?string
    {
        return $this->content;
    }

    public function getLevel(): int
    {
        return $this->level;
    }

    public function getHn(): string
    {
        return sprintf('h%d', $this->level);
    }

    public function isParent(): bool
    {
        return $this->parent !== null;
    }

    public function getParent(): ?Headline
    {
        return $this->parent;
    }

    public function getParentForLevel(int $level): ?Headline
    {
        if ($this->level < $level) {
            return $this;
        }

        if ($this->parent === null) {
            return null;
        }

        return $this->parent->getParentForLevel($level);
    }

    public function jsonSerialize(): array
    {
        return [
            'id' => $this->id,
            'level' => $this->level,
            'content' => $this->content,
            'children' => array_map(fn ($child) => $child->jsonSerialize(), $this->children),
        ];
    }
}
