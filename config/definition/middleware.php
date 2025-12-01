<?php

declare(strict_types=1);

use RunOpenCode\Component\Query\Replica\FallbackStrategy;
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
                ->defaultValue([
                    'cache',
                    'parser',
                    'executor',
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
                        ->end()
                    ->end()
                ->end()
                ->arrayNode('retry')
                    ->scalarPrototype()->end()
                    ->defaultValue(null)
                    ->validate()
                        ->ifFalse(static function(?array $value): bool {
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
                        })
                        ->thenInvalid('Retry middleware expects a list of full qualified class names which extends "\Exception".')
                    ->end()
                ->end()
            ->end()
        ->end();
};
