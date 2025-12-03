<?php

declare(strict_types=1);

use Symfony\Component\Config\Definition\Configurator\DefinitionConfigurator;

return static function(DefinitionConfigurator $definition): void {
    // @phpstan-ignore-next-line
    $definition
        ->rootNode()
        ->addDefaultsIfNotSet()
        ->children()
            ->scalarNode('default_connection')
                ->defaultNull()
                ->info('Default connection name. If not provided, first registered adapter connection will be considered as default one.')
            ->end()
            ->scalarNode('default_query_path')
                ->info('The default path used to load queries.')
                ->defaultValue('%kernel.project_dir%/query')
            ->end()
            ->arrayNode('query_paths')
                ->info('Additional paths where queries can be found.')
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

        ->end();
};
