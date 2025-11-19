<?php

declare(strict_types=1);

use RunOpenCode\Bundle\QueryBundle\CacheWarmer\TwigCacheWarmer;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Twig\Environment;
use Twig\Loader\ChainLoader;
use Twig\Loader\FilesystemLoader;

use function Symfony\Component\DependencyInjection\Loader\Configurator\service;
use function Symfony\Component\DependencyInjection\Loader\Configurator\tagged_iterator;

return static function(ContainerConfigurator $container): void {
    $configurator = $container->services();

    $configurator
        ->set('runopencode.query.twig.loader.filesystem', FilesystemLoader::class)
        ->tag('runopencode.query.twig.loader');

    $configurator
        ->set('runopencode.query.twig.loader', ChainLoader::class)
        ->arg('$loaders', tagged_iterator('runopencode.query.twig.loader'));

    $configurator
        ->set('runopencode.query.twig', Environment::class)
        ->arg('$loader', service('runopencode.query.twig.loader'))
        ->public();

    $configurator
        ->set(TwigCacheWarmer::class)
        ->arg('$container', service('service_container'))
        ->tag('kernel.cache_warmer');
};
