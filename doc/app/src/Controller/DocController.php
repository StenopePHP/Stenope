<?php

namespace App\Controller;

use App\Model\Index;
use App\Model\Page;
use Stenope\Bundle\ContentManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;

class DocController extends AbstractController
{
    public function __construct(
        private ContentManagerInterface $contentManager
    ) {
    }

    #[Route('/', name: 'index')]
    public function index()
    {
        $page = $this->contentManager->getContent(Index::class, 'README');

        return $this->render('doc/index.html.twig', ['page' => $page]);
    }

    #[Route('/{page<.+>}', name: 'page')]
    public function page(Page $page)
    {
        return $this->render('doc/page.html.twig', ['page' => $page]);
    }
}
