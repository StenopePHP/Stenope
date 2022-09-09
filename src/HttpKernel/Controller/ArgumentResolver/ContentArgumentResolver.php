<?php

/*
 * This file is part of the "StenopePHP/Stenope" bundle.
 *
 * @author Thomas Jarrand <thomas.jarrand@gmail.com>
 */

namespace Stenope\Bundle\HttpKernel\Controller\ArgumentResolver;

use Stenope\Bundle\ContentManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Controller\ArgumentValueResolverInterface;
use Symfony\Component\HttpKernel\Controller\ValueResolverInterface;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;

// Legacy (<6.2) resolver
if (!interface_exists(ValueResolverInterface::class)) {
    /**
     * @final
     */
    class ContentArgumentResolver implements ArgumentValueResolverInterface
    {
        private ContentManagerInterface $contentManager;

        public function __construct(ContentManagerInterface $contentManager)
        {
            $this->contentManager = $contentManager;
        }

        public function resolve(Request $request, ArgumentMetadata $argument): iterable
        {
            $slug = $request->attributes->get($argument->getName());

            if (null === $slug) {
                throw new \LogicException(sprintf('No value provided in the route attributes for the $%s argument of type "%s". Did your forget to make it nullable?', $argument->getName(), $argument->getType()));
            }

            yield $this->contentManager->getContent($argument->getType(), $slug);
        }

        public function supports(Request $request, ArgumentMetadata $argument): bool
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

    return;
}

/**
 * @final
 */
class ContentArgumentResolver implements ValueResolverInterface
{
    private ContentManagerInterface $contentManager;

    public function __construct(ContentManagerInterface $contentManager)
    {
        $this->contentManager = $contentManager;
    }

    public function resolve(Request $request, ArgumentMetadata $argument): iterable
    {
        if (null === $argument->getType() || !$this->contentManager->supports($argument->getType())) {
            return [];
        }

        $slug = $request->attributes->get($argument->getName());

        // Let the other resolvers (e.g: the default value resolver) try to handle it if no slug is provided:
        if (null === $slug && $argument->isNullable()) {
            return [];
        }

        if (null === $slug) {
            throw new \LogicException(sprintf('No value provided in the route attributes for the $%s argument of type "%s". Did your forget to make it nullable?', $argument->getName(), $argument->getType()));
        }

        return [$this->contentManager->getContent($argument->getType(), $slug)];
    }
}
