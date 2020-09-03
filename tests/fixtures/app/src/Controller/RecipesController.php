<?php

/*
 * This file is part of the "Tom32i/Content" bundle.
 *
 * @author Thomas Jarrand <thomas.jarrand@gmail.com>
 */

namespace App\Controller;

use App\Content\Model\Recipe;
use Content\ContentManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/recipes")
 */
class RecipesController extends AbstractController
{
    private ContentManager $manager;

    public function __construct(ContentManager $manager)
    {
        $this->manager = $manager;
    }

    /**
     * @Route("/", name="recipes")
     */
    public function index()
    {
        $recipes = $this->manager->getContents(Recipe::class, ['date' => false]);
        $lastModified = max(array_map(fn (Recipe $recipe): \DateTimeInterface => $recipe->lastModified, $recipes));

        return $this->render('recipe/index.html.twig', [
            'recipes' => $recipes,
        ])->setLastModified($lastModified);
    }

    /**
     * @Route("/{slug}", name="recipe")
     */
    public function show(string $slug)
    {
        $recipe = $this->manager->getContent(Recipe::class, $slug);

        return $this->render('recipe/show.html.twig', [
            'recipe' => $recipe,
        ])->setLastModified($recipe->lastModified);
    }
}
