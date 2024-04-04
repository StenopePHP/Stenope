<?php

/*
 * This file is part of the "StenopePHP/Stenope" bundle.
 *
 * @author Thomas Jarrand <thomas.jarrand@gmail.com>
 * @author Maxime Steinhausser <maxime.steinhausser@gmail.com>
 */

namespace App\Controller;

use App\Model\Author;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route(path="/authors")
 */
class AuthorsController extends AbstractController
{
    /**
     * @Route(path="/{author<[\w.]+>}.json", name="author_json", options={
     *     "stenope": {
     *         "sitemap": false,
     *     },
     * })
     */
    public function showAsJson(Author $author)
    {
        return $this->json([
            'slug' => $author->slug,
            'firstname' => $author->firstname,
            'lastname' => $author->lastname,
            'nickname' => $author->nickname,
            'tags' => $author->tags,
        ]);
    }

    /**
     * @Route(path="/{author}", name="author")
     */
    public function show(Author $author)
    {
        return $this->render('author/show.html.twig', [
            'author' => $author,
        ])->setLastModified($author->lastModified);
    }
}
