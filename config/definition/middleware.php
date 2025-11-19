<?php

declare(strict_types=1);

use RunOpenCode\Component\Query\Doctrine\Replica\FallbackStrategy;
use Symfony\Component\Config\Definition\Configurator\DefinitionConfigurator;

return static function(DefinitionConfigurator $definition): void {
    // @phpstan-ignore-next-line
    $definition
        ->rootNode()
        ->addDefaultsIfNotSet()
        ->children()
            ->arrayNode('middlewares')
            ->addDefaultsIfNotSet()
            ->children()
                ->arrayNode('stack')
                ->addDefaultsIfNotSet()
                ->defaultValue([
                    'cache',
                    'parser',
                    'executor',
                ])
                    ->scalarPrototype()
                ->end()
                ->arrayNode('replica')
                    ->useAttributeAsKey('connection')
                    ->arrayPrototype()
                        ->children()
                            ->arrayNode('replicas')
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
                        ->end()
                    ->end()
                ->end()
            ->end()
        ->end();
};
