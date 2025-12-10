<?php

declare(strict_types=1);

use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use RunOpenCode\Component\Query\Replica\FallbackStrategy;
use Symfony\Component\Config\Definition\Configurator\DefinitionConfigurator;

return static function(DefinitionConfigurator $definition): void {
    $assertCatchable = static function(?array $value): bool {
        if (null === $value) {
            return true;
        }

        foreach ($value as $current) {
            if (\is_string($current) && \is_a($current, \Exception::class, true)) {
                continue;
            }

            return false;
        }

        return true;
    };

    $definition
        ->rootNode()
        ->addDefaultsIfNotSet()
        ->children()
            ->arrayNode('middlewares')
            ->addDefaultsIfNotSet()
            ->children()
                ->arrayNode('stack')
                ->defaultValue([
                    'cache',
                    'parser',
                ])
                    ->scalarPrototype()->end()
                ->end()
                ->arrayNode('replica')
                    ->arrayPrototype()
                        ->children()
                            ->arrayNode('replica')
                                ->beforeNormalization()
                                    ->ifString()
                                    ->then(static fn(string $value): array => (array) $value)
                                ->end()
                                ->isRequired()
                                ->scalarPrototype()->end()
                            ->end()
                            ->enumNode('fallback')
                                ->enumFqcn(FallbackStrategy::class)
                                ->defaultValue(FallbackStrategy::Primary)
                            ->end()
                            ->booleanNode('disabled')
                                ->defaultValue('%kernel.debug%')
                            ->end()
                            ->arrayNode('catch')
                                ->scalarPrototype()->end()
                                ->defaultValue([])
                                ->validate()
                                    ->ifFalse($assertCatchable)
                                    ->thenInvalid('Replica middleware expects a list of full qualified class names which extends "\Exception" to catch.')
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
                ->arrayNode('retry')
                    ->scalarPrototype()->end()
                    ->defaultValue([])
                    ->validate()
                        ->ifFalse($assertCatchable)
                        ->thenInvalid('Retry middleware expects a list of full qualified class names which extends "\Exception".')
                    ->end()
                ->end()
                ->arrayNode('slow')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->scalarNode('logger')
                            ->defaultValue(LoggerInterface::class)
                        ->end()
                        ->scalarNode('level')
                            ->defaultValue(LogLevel::ERROR)
                        ->end()
                        ->integerNode('threshold')
                            ->min(1)
                            ->defaultValue(30)
                        ->end()
                        ->booleanNode('always')
                            ->defaultFalse()
                        ->end()
                    ->end()
                ->end()
                ->arrayNode('cache')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->scalarNode('cache_pool')
                            ->defaultValue('cache.app')
                            ->info('Cache pool to use for cache middleware. By default `cache.app` is used. If `NULL` is provided, middleware will be still registered, but `Symfony\Component\Cache\Adapter\NullAdapter` will be used (no caching). ')
                        ->end()
                    ->end()
                ->end()
            ->end()
        ->end();
};
