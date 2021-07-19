<?php

/*
 * This file is part of the "StenopePHP/Stenope" bundle.
 *
 * @author Thomas Jarrand <thomas.jarrand@gmail.com>
 */

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class DefaultController extends AbstractController
{
    /**
     * @Route("/", name="homepage")
     */
    public function index()
    {
        $response = $this->render('homepage.html.twig');

        $response->headers->set('X-Robots-Tag', 'noindex');

        return $response;
    }

    /**
     * @Route("/foo.html", name="foo_html")
     */
    public function foo()
    {
        return new Response('foo');
    }
}
