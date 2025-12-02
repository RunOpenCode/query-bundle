<?php

declare(strict_types=1);

use RunOpenCode\Component\Query\Contract\ExecutorInterface;
use RunOpenCode\Component\Query\Executor;
use RunOpenCode\Component\Query\Executor\AdapterRegistry;
use RunOpenCode\Component\Query\Middleware\MiddlewareChain;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

use function Symfony\Component\DependencyInjection\Loader\Configurator\param;
use function Symfony\Component\DependencyInjection\Loader\Configurator\service;
use function Symfony\Component\DependencyInjection\Loader\Configurator\tagged_iterator;

return static function(ContainerConfigurator $container): void {
    $configurator = $container->services();

    $configurator
        ->set(AdapterRegistry::class)
        ->arg('$adapters', tagged_iterator('runopencode.query.adapter'))
        ->arg('$default', param('runopencode.query.default_connection'));

    $configurator
        ->set(MiddlewareChain::class);


    $configurator
        ->set(Executor::class)
        ->arg('$middlewares', service(MiddlewareChain::class))
        ->arg('$adapters', service(AdapterRegistry::class));

    $configurator
        ->alias(ExecutorInterface::class, Executor::class);
    
    $configurator
        ->alias('runopencode.query', Executor::class);
};
