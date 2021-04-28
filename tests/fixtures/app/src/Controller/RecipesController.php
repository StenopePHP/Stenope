<?php

/*
 * This file is part of the "StenopePHP/Stenope" bundle.
 *
 * @author Thomas Jarrand <thomas.jarrand@gmail.com>
 */

namespace App\Controller;

use App\Model\Recipe;
use Stenope\Bundle\ContentManager;
use Stenope\Bundle\HttpKernel\Controller\ArgumentResolver\ContentArgumentResolver;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
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
     * Ensure {@link ContentArgumentResolver} handles nullable arguments properly.
     *
     * @Route("/optional-recipe", name="optional-recipe", options={
     *     "stenope": {
     *         "ignore": true,
     *     },
     * })
     */
    public function optionalRecipe(?Recipe $recipe)
    {
        return new Response('OK');
    }

    /**
     * @Route("/{recipe}.pdf", name="recipe_pdf", options={
     *     "stenope": {
     *         "sitemap": false,
     *     },
     * })
     */
    public function downloadAsPdf(Recipe $recipe)
    {
        $response = $this->file(__DIR__ . '/../../var/pdf/dummy.pdf', "{$recipe->slug}.pdf");

        $response->headers->set('Content-Type', 'application/pdf');

        return $response;
    }

    /**
     * @Route("/{recipe}", name="recipe")
     */
    public function show(Recipe $recipe)
    {
        return $this->render('recipe/show.html.twig', [
            'recipe' => $recipe,
        ])->setLastModified($recipe->lastModified);
    }
}
