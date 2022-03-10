<?php

/*
 * This file is part of the "StenopePHP/Stenope" bundle.
 *
 * @author Thomas Jarrand <thomas.jarrand@gmail.com>
 */

namespace Stenope\Bundle\Controller;

use Stenope\Bundle\Content\GenericContent;
use Stenope\Bundle\ContentManagerInterface;
use Stenope\Bundle\Exception\ContentNotFoundException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Twig\Environment;

class GenericContentController extends AbstractController
{
    private ContentManagerInterface $contentManager;
    private Environment $twig;

    public function __construct(ContentManagerInterface $contentManager, Environment $twig)
    {
        $this->contentManager = $contentManager;
        $this->twig = $twig;
    }

    public function list(string $type)
    {
        $contents = $this->contentManager->getContents(
            GenericContent::class,
            'slug',
            sprintf('"%s" in _.types', $type)
        );

        if (empty($contents)) {
            throw new NotFoundHttpException(sprintf('No content found for type "%s"', $type));
        }

        return $this->render($this->getListTemplate($type), [
            'contents' => $contents,
            'type' => $type,
            'types' => GenericContent::expandTypes($type),
        ]);
    }

    public function show(string $slug)
    {
        try {
            $content = $this->contentManager->getContent(GenericContent::class, $slug);
        } catch (ContentNotFoundException $exception) {
            throw new NotFoundHttpException(sprintf('No content found for slug "%s"', $slug));
        }

        return $this->render($this->getShowTemplate($content), [
            'content' => $content,
        ]);
    }

    private function getListTemplate(string $type): string
    {
        $attempts = [];

        foreach (GenericContent::expandTypes($type) as $previousType) {
            if ($this->twig->getLoader()->exists($attempts[] = $template = "$previousType/list.html.twig")) {
                return $template;
            }
        }

        if ($this->twig->getLoader()->exists($attempts[] = $template = 'stenope/list.html.twig')) {
            return $template;
        }

        throw new \LogicException(sprintf(
            'No template available to render the "%s" type of contents. Attempted %s. Please create one of these.',
            $type,
            json_encode($attempts, JSON_UNESCAPED_SLASHES)
        ));
    }

    private function getShowTemplate(GenericContent $content): string
    {
        if ($content->template) {
            return $content->template;
        }

        $attempts = [];
        if ($this->twig->getLoader()->exists($attempts[] = $template = "$content->slug.html.twig")) {
            return $template;
        }

        foreach ($content->types as $type) {
            if ($content->type && $this->twig->getLoader()->exists($attempts[] = $template = "$type/show.html.twig")) {
                return $template;
            }
        }

        if ($this->twig->getLoader()->exists($attempts[] = $template = 'stenope/show.html.twig')) {
            return $template;
        }

        throw new \LogicException(sprintf(
            'No template available to render the "%s" content. Attempted %s. Please create one of these, or provide explicitly the template to use with the "template" property.',
            $content->slug,
            json_encode($attempts, JSON_UNESCAPED_SLASHES)
        ));
    }
}
