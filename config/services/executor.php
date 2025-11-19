<?php

declare(strict_types=1);

use RunOpenCode\Component\Query\Contract\ExecutorInterface;
use RunOpenCode\Component\Query\Executor;
use RunOpenCode\Component\Query\Executor\AdapterRegistry;
use RunOpenCode\Component\Query\Executor\ExecutorMiddleware;
use RunOpenCode\Component\Query\Middleware\MiddlewareRegistry;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

use function Symfony\Component\DependencyInjection\Loader\Configurator\service;
use function Symfony\Component\DependencyInjection\Loader\Configurator\tagged_iterator;

return static function(ContainerConfigurator $container): void {
    $configurator = $container->services();

    $configurator
        ->set(AdapterRegistry::class)
        ->arg('$executors', tagged_iterator('runopencode.query.adapter'));

    $configurator
        ->set(MiddlewareRegistry::class);

    $configurator
        ->set(ExecutorMiddleware::class)
        ->arg('$registry', service(AdapterRegistry::class))
        ->tag('runopencode.query.middleware', [
            'alias' => 'executor',
        ]);

    $configurator
        ->set(Executor::class)
        ->arg('$middlewares', MiddlewareRegistry::class)
        ->arg('$adapters', AdapterRegistry::class);

    $configurator
        ->alias(ExecutorInterface::class, Executor::class);
};
