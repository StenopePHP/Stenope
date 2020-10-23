<?php

/*
 * This file is part of the "StenopePHP/Stenope" bundle.
 *
 * @author Thomas Jarrand <thomas.jarrand@gmail.com>
 */

namespace App\Model;

class Author
{
    public string $slug;
    public string $firstname;
    public string $lastname;
    public string $nickname;
    public array $tags;
    public \DateTimeInterface $lastModified;
}
