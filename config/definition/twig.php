<?php

declare(strict_types=1);

use Symfony\Component\Config\Definition\Configurator\DefinitionConfigurator;

return static function(DefinitionConfigurator $definition): void {
    // @phpstan-ignore-next-line
    $definition
        ->rootNode()
        ->addDefaultsIfNotSet()
        ->children()
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
                    ->scalarNode('default_path')
                        ->info('The default path used to load queries.')
                        ->defaultValue('%kernel.project_dir%/query')
                    ->end()
                    ->arrayNode('file_name_pattern')
                        ->example('*.twig')
                        ->info('Pattern of file name used for cache warmer and linter.')
                        ->beforeNormalization()
                            ->ifString()
                            ->then(fn($value) => [$value])
                        ->end()
                        ->prototype('scalar')->end()
                    ->end()
                    ->arrayNode('paths')
                        ->info('Additional paths where queries can be found.')
                        ->useAttributeAsKey('paths')
                        ->defaultValue([])
                        ->beforeNormalization()
                            ->ifArray()
                            ->then(static function(array $paths): array {
                                $normalized = [];

                                /**
                                 * @var string|int $path
                                 * @var string     $namespace
                                 */
                                foreach ($paths as $path => $namespace) {
                                    // path within the default namespace
                                    if (\ctype_digit((string) $path)) {
                                        $path = $namespace;
                                        $namespace = null;
                                    }

                                    $normalized[$path] = $namespace;
                                }

                                return $normalized;
                            })
                        ->end()
                        ->prototype('variable')->end()
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
                                ->ifTrue(function($v) {
                                    if (\is_array($v)) {
                                        $keys = \array_keys($v);
                                        \sort($keys);

                                        return $keys !== ['id', 'type'] && $keys !== ['value'];
                                    }

                                    return true;
                                })
                                ->then(fn($v) => ['value' => $v])
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
        ->end();
};
