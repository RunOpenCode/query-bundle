<?php

declare(strict_types=1);

use RunOpenCode\Component\Query\Cache\CacheMiddleware;
use RunOpenCode\Component\Query\Cache\Invalidate;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function(ContainerConfigurator $container): void {
    $configurator = $container->services();

    $configurator
        ->set(CacheMiddleware::class)
        ->arg('$cache', null)
        ->tag('runopencode.query.middleware', [
            'alias' => 'cache',
        ])
        ->tag('kernel.event_listener', [
            'event'  => Invalidate::class,
            'method' => 'invalidate',
        ]);
};
