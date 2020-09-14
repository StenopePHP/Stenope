<?php

/*
 * This file is part of the "Tom32i/Content" bundle.
 *
 * @author Thomas Jarrand <thomas.jarrand@gmail.com>
 */

namespace App\Controller;

use App\Content\Model\Author;
use Content\ContentManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/authors")
 */
class AuthorsController extends AbstractController
{
    private ContentManager $manager;

    public function __construct(ContentManager $manager)
    {
        $this->manager = $manager;
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
