<?php

namespace App\Model;

use Stenope\Bundle\TableOfContent\Headline;

class Page
{
    public string $title;
    public string $slug;
    public string $content;
    /** @var Headline[] */
    public array $tableOfContent = [];
    public \DateTimeInterface $created;
    public \DateTimeInterface $lastModified;
}
