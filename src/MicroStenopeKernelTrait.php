<?php

/*
 * This file is part of the "StenopePHP/Stenope" bundle.
 *
 * @author Thomas Jarrand <thomas.jarrand@gmail.com>
 */

namespace Stenope\Bundle;

use Stenope\Bundle\Content\GenericContent;
use Stenope\Bundle\Controller\GenericContentController;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;

trait MicroStenopeKernelTrait
{
    protected function configureStenope(ContainerConfigurator $container): void
    {
        $container->extension('stenope', [
            'providers' => [
                GenericContent::class => '%kernel.project_dir%/content',
            ],
            'resolve_links' => [
                GenericContent::class => [
                    'route' => 'stenope_show',
                    'slug' => 'slug',
                ],
            ],
        ]);
    }

    protected function configureStenopeRoutes(
        RoutingConfigurator $routes,
        string $showPath = '/{slug}',
        string $listPath = '/{type}/list',
        string $prefix = ''
    ): void {
        $routes
            ->add('stenope_list', "$prefix/$listPath")
                ->controller(GenericContentController::class . '::list')
                ->requirements([
                    'type' => '.+',
                ])
            ->add('stenope_show', "$prefix/$showPath")
                ->controller(GenericContentController::class . '::show')
                ->requirements([
                    'slug' => '.+[^/]$',
                ])
        ;
    }
}
