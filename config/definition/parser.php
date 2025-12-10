<?php

declare(strict_types=1);

use Symfony\Component\Config\Definition\Configurator\DefinitionConfigurator;

return static function(DefinitionConfigurator $definition): void {
    // @phpstan-ignore-next-line
    $definition
        ->rootNode()
        ->addDefaultsIfNotSet()
        ->children()
            ->arrayNode('parser')
                ->addDefaultsIfNotSet()
                ->children()
                    ->arrayNode('file')
                        ->addDefaultsIfNotSet()
                        ->children()
                            ->arrayNode('pattern')
                                ->defaultValue(['*.sql', '*.dql'])
                                ->beforeNormalization()
                                    ->ifString()
                                    ->then(static fn($value): array => [$value])
                                ->end()
                                ->scalarPrototype()->end()
                            ->end()
                        ->end()
                    ->end()
                    ->arrayNode('twig')
                        ->info('Configuration of Twig environment for Twig query parser.')
                        ->addDefaultsIfNotSet()
                        ->children()
                            ->scalarNode('cache')
                                ->defaultValue('%kernel.cache_dir%/runopencode/query/twig')
                                ->info('Where to store cache of compiled Twig templates.')
                            ->end()
                            ->scalarNode('charset')
                                ->defaultValue('%kernel.charset%')
                            ->end()
                            ->booleanNode('debug')
                                ->info('Where to enable debug mode, by default uses `kernel.debug` parameter value.')
                                ->defaultValue('%kernel.debug%')
                            ->end()
                            ->booleanNode('strict_variables')
                                ->info('Whether to ignore invalid variables in templates.')
                                ->defaultValue('%kernel.debug%')
                            ->end()
                            ->scalarNode('auto_reload')->end()
                            ->integerNode('optimizations')
                                ->min(-1)
                            ->end()
                            ->arrayNode('pattern')
                                ->example('*.twig')
                                ->info('Pattern of file names to parse with Twig parser.')
                                ->beforeNormalization()
                                    ->ifString()
                                    ->then(static fn($value): array => [$value])
                                ->end()
                                ->defaultValue(['*.twig'])
                                ->prototype('scalar')->end()
                            ->end()
                            ->arrayNode('globals')
                                ->info('Global variables available to all queries.')
                                ->useAttributeAsKey('key')
                                ->example(['foo' => '@bar', 'pi' => 3.14])
                                ->prototype('array')
                                    ->normalizeKeys(false)
                                    ->beforeNormalization()
                                        ->ifTrue(fn(mixed $v): bool => \is_string($v) && \str_starts_with($v, '@'))
                                        ->then(function(string $v): string|array {
                                            if (\str_starts_with($v, '@@')) {
                                                return \substr($v, 1);
                                            }

                                            return ['id' => \substr($v, 1), 'type' => 'service'];
                                        })
                                    ->end()
                                    ->beforeNormalization()
                                        ->ifTrue(function($v): bool {
                                            if (\is_array($v)) {
                                                $keys = \array_keys($v);
                                                \sort($keys);

                                                return $keys !== ['id', 'type'] && $keys !== ['value'];
                                            }

                                            return true;
                                        })
                                        ->then(fn($v): array => ['value' => $v])
                                    ->end()
                                    ->children()
                                        ->scalarNode('id')->end()
                                        ->scalarNode('type')
                                            ->validate()
                                            ->ifNotInArray(['service'])
                                            ->thenInvalid('The %s type is not supported')
                                            ->end()
                                        ->end()
                                        ->variableNode('value')->end()
                                    ->end()
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end()
        ->end();
};
