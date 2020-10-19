<?php

namespace App\Controller;

use App\Model\Page;
use Content\ContentManager;
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
        $page = $this->contentManager->getContent(Page::class, 'README');

        return $this->render('doc/index.html.twig', ['page' => $page]);
    }

    /**
     * @Route("/{name}", name="page")
     */
    public function page(string $name)
    {
        $page = $this->contentManager->getContent(Page::class, sprintf('doc/%s', $name));

        return $this->render('doc/page.html.twig', ['page' => $page]);
    }
}
