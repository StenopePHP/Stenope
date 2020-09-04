<?php

/*
 * This file is part of the "Tom32i/Content" bundle.
 *
 * @author Thomas Jarrand <thomas.jarrand@gmail.com>
 */

namespace App\Content\Provider;

use App\Content\Model\Recipe;
use Content\Behaviour\ContentProviderInterface;

class RecipeProvider implements ContentProviderInterface
{
    public function getDirectory(): string
    {
        return 'recipes';
    }

    public function supports(string $className): bool
    {
        return Recipe::class;
    }
}
