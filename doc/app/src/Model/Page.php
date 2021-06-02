<?php

namespace App\Model;

use Stenope\Bundle\TableOfContent\TableOfContent;

class Page
{
    public string $title;
    public string $slug;
    public string $content;
    public ?TableOfContent $tableOfContent = null;
    public \DateTimeInterface $created;
    public \DateTimeInterface $lastModified;
}
