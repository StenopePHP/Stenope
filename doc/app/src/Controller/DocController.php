<?php

namespace App\Controller;

use App\Model\Index;
use App\Model\Page;
use Stenope\Bundle\ContentManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;

class DocController extends AbstractController
{
    private ContentManager $contentManager;

    public function __construct(ContentManager $contentManager)
    {
        $this->contentManager = $contentManager;
    }

    /**
     * @Route("/", name="index")
     */
    public function index()
    {
        $page = $this->contentManager->getContent(Index::class, 'README');

        return $this->render('doc/index.html.twig', ['page' => $page]);
    }

    /**
     * @Route("/{page}", name="page", options={
     *     "stenope": {
     *         "show": {
     *              "class": \App\Model\Page::class,
     *              "slug": "page",
     *         },
     *     },
     * })
     */
    public function page(Page $page)
    {
        return $this->render('doc/page.html.twig', ['page' => $page]);
    }
}
