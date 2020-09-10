<?php

/*
 * This file is part of the "Tom32i/Content" bundle.
 *
 * @author Thomas Jarrand <thomas.jarrand@gmail.com>
 */

namespace Content\HttpKernel\Controller\ArgumentResolver;

use Content\ContentManager;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Controller\ArgumentValueResolverInterface;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;

class ContentArgumentResolver implements ArgumentValueResolverInterface
{
    private ContentManager $contentManager;

    public function __construct(ContentManager $contentManager)
    {
        $this->contentManager = $contentManager;
    }

    public function resolve(Request $request, ArgumentMetadata $argument)
    {
        $slug = $request->attributes->get($argument->getName());

        if (null === $slug) {
            throw new \LogicException(sprintf('No value provided in the route attributes for the $%s argument of type "%s". Did your forget to make it nullable?', $argument->getName(), $argument->getType()));
        }

        yield $this->contentManager->getContent($argument->getType(), $slug);
    }

    public function supports(Request $request, ArgumentMetadata $argument)
    {
        if (null === $argument->getType() || !$this->contentManager->supports($argument->getType())) {
            return false;
        }

        $slug = $request->attributes->get($argument->getName());

        // Let the other resolvers (e.g: the default value resolver) try to handle it if no slug is provided:
        if (null === $slug && $argument->isNullable()) {
            return false;
        }

        return true;
    }
}
