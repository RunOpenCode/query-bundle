<?php

declare(strict_types=1);

use Symfony\Component\Config\Definition\Configurator\DefinitionConfigurator;

return static function(DefinitionConfigurator $definition): void {
    // @phpstan-ignore-next-line
    $definition
        ->rootNode()
        ->addDefaultsIfNotSet()
        ->children()
            ->scalarNode('cache_pool')
                ->defaultValue('app.cache')
                ->info(<<<INFO
Cache pool to use for cache middleware. By default `app.cache` is used. If `NULL` is provided, middleware will be still
registered, but `Symfony\Component\Cache\Adapter\NullAdapter` will be used (no caching). 
INFO)
            ->end()
        ->end();
};
