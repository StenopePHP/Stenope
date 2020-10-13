<?php

/*
 * This file is part of the "Tom32i/Content" bundle.
 *
 * @author Thomas Jarrand <thomas.jarrand@gmail.com>
 */

namespace App\Controller;

use App\Content\Model\Author;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/authors")
 */
class AuthorsController extends AbstractController
{
    /**
     * @Route("/{author<[\w.]+>}.json", name="author_json", format="json", options={
     *     "content": {
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
     * @Route("/{author}", name="author")
     */
    public function show(Author $author)
    {
        return $this->render('author/show.html.twig', [
            'author' => $author,
        ])->setLastModified($author->lastModified);
    }
}
