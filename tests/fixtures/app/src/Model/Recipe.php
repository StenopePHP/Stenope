<?php

/*
 * This file is part of the "StenopePHP/Stenope" bundle.
 *
 * @author Thomas Jarrand <thomas.jarrand@gmail.com>
 */

namespace App\Model;

class Recipe
{
    public string $title;
    public ?string $description = null;
    public string $slug;
    public string $content;
    public array $authors;
    public array $tags;
    public \DateTimeInterface $date;
    public \DateTimeInterface $lastModified;
}
