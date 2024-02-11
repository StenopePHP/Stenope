<?php

/*
 * This file is part of the "StenopePHP/Stenope" bundle.
 *
 * @author Thomas Jarrand <thomas.jarrand@gmail.com>
 * @author Maxime Steinhausser <maxime.steinhausser@gmail.com>
 */

namespace App\Model;

use Stenope\Bundle\TableOfContent\TableOfContent;

class Recipe
{
    public string $title;
    public ?string $description = null;
    public string $slug;
    public string $content;
    public ?TableOfContent $tableOfContent = null;
    public array $authors;
    public array $tags;
    public \DateTimeInterface $date;
    public \DateTimeInterface $lastModified;
}
