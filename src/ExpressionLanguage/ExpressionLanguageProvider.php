<?php

/*
 * This file is part of the "StenopePHP/Stenope" bundle.
 *
 * @author Thomas Jarrand <thomas.jarrand@gmail.com>
 */

namespace Stenope\Bundle\ExpressionLanguage;

use Symfony\Component\ExpressionLanguage\ExpressionFunction;
use Symfony\Component\ExpressionLanguage\ExpressionFunctionProviderInterface;

/**
 * @internal
 */
class ExpressionLanguageProvider implements ExpressionFunctionProviderInterface
{
    /** @var iterable<ExpressionFunctionProviderInterface> */
    private iterable $providers;

    public function __construct(iterable $providers = [])
    {
        $this->providers = $providers;
    }

    public function getFunctions(): iterable
    {
        // prepend the default functions to let users override these easily:
        yield from [
            new ExpressionFunction('date', function ($arg) {
                return sprintf('(new \DateTimeImmutable(%s))->setTime(0, 0)', $arg);
            }, function (array $variables, $value) {
                return (new \DateTimeImmutable($value))->setTime(0, 0);
            }),
            new ExpressionFunction('datetime', function ($arg) {
                return sprintf('new \DateTimeImmutable(%s)', $arg);
            }, function (array $variables, $value) {
                return new \DateTimeImmutable($value);
            }),
            ExpressionFunction::fromPhp('strtoupper', 'upper'),
            ExpressionFunction::fromPhp('strtolower', 'lower'),
            ExpressionFunction::fromPhp('str_contains', 'contains'),
            ExpressionFunction::fromPhp('str_starts_with', 'starts_with'),
            ExpressionFunction::fromPhp('str_ends_with', 'ends_with'),
            ExpressionFunction::fromPhp('array_keys', 'keys'),
        ];

        foreach ($this->providers as $provider) {
            yield from $provider->getFunctions();
        }
    }
}
