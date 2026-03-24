<?php declare(strict_types=1);

namespace App;

use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Kernel as BaseKernel;
use Symfony\Component\Routing\RouteCollectionBuilder;
use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

final class Kernel extends BaseKernel
{
    use MicroKernelTrait;

    protected function configureContainer(ContainerConfigurator $container, LoaderInterface $loader, ContainerBuilder $builder): void
    {
        $container->import('../config/{packages}/*.yaml');
        $container->import('../config/{packages}/'.$this->environment.'/*.yaml');
        $container->import('../config/services.yaml');
    }

    protected function configureRoutes(RoutingConfigurator $routes): void
    {
        $routes->import('../config/routes.yaml');
    }
}
