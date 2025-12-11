<?php

declare(strict_types=1);

use Psr\Log\NullLogger;
use RunOpenCode\Component\Query\Cache\CacheMiddleware;
use RunOpenCode\Component\Query\Cache\Invalidate;
use RunOpenCode\Component\Query\Doctrine\Dbal\Middleware\ConvertMiddleware;
use RunOpenCode\Component\Query\Executor\AdapterRegistry;
use RunOpenCode\Component\Query\Executor\ExecutorMiddleware;
use RunOpenCode\Component\Query\Monitor\SlowExecutionMiddleware;
use RunOpenCode\Component\Query\Parser\ParserMiddleware;
use RunOpenCode\Component\Query\Parser\ParserRegistry;
use RunOpenCode\Component\Query\Retry\RetryMiddleware;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

use function Symfony\Component\DependencyInjection\Loader\Configurator\service;

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

    $configurator
        ->set(ParserMiddleware::class)
        ->arg('$registry', service(ParserRegistry::class))
        ->tag('runopencode.query.middleware', [
            'alias' => 'parser',
        ]);

    $configurator
        ->set(RetryMiddleware::class)
        ->tag('runopencode.query.middleware', [
            'alias' => 'retry',
        ]);

    $configurator
        ->set('runopencode.query.null_logger', NullLogger::class);

    $configurator
        ->set(SlowExecutionMiddleware::class)
        ->tag('runopencode.query.middleware', [
            'alias' => 'slow',
        ]);

    $configurator
        ->set(ConvertMiddleware::class)
        ->arg('$registry', service(AdapterRegistry::class))
        ->tag('runopencode.query.middleware', [
            'alias' => 'convert',
        ]);

    $configurator
        ->set(ExecutorMiddleware::class)
        ->arg('$registry', service(AdapterRegistry::class))
        ->tag('runopencode.query.middleware', [
            'alias' => 'executor',
        ]);
};
