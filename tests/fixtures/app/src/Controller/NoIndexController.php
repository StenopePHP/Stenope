<?php

/*
 * This file is part of the "StenopePHP/Stenope" bundle.
 *
 * @author Thomas Jarrand <thomas.jarrand@gmail.com>
 */

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;

class NoIndexController extends AbstractController
{
    /**
     * @Route(path="/with-noindex", name="with_noindex")
     */
    public function withNoIndex()
    {
        $response = $this->render('noindex/with.html.twig');

        $response->headers->set('X-Robots-Tag', 'noindex');

        return $response;
    }

    /**
     * @Route(path="/without-noindex", name="without_noindex")
     */
    public function withoutNoIndex()
    {
        $response = $this->render('noindex/without.html.twig');

        $response->headers->set('X-Robots-Tag', 'noindex');

        return $response;
    }
}
