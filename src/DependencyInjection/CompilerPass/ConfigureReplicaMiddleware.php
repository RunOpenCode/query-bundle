<?php

declare(strict_types=1);

namespace RunOpenCode\Bundle\QueryBundle\DependencyInjection\CompilerPass;

use RunOpenCode\Bundle\QueryBundle\QueryBundle;
use RunOpenCode\Component\Query\Executor\AdapterRegistry;
use RunOpenCode\Component\Query\Replica\ReplicaMiddleware;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

/**
 * @phpstan-import-type ReplicaMiddlewareConfig from QueryBundle
 */
final readonly class ConfigureReplicaMiddleware implements CompilerPassInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container): void
    {
        if (!$container->hasParameter('.runopencode.query.configuration.middlewares.replica')) {
            return;
        }

        /**
         * @var array<non-empty-string, ReplicaMiddlewareConfig> $configuration
         */
        $configuration = $container->getParameter('.runopencode.query.configuration.middlewares.replica');

        foreach ($configuration as $connection => $parameters) {
            $id         = \sprintf('runopencode.query.middleware.replica.%s', $connection);
            $alias      = \sprintf('replica.%s', $connection);
            $definition = new Definition(ReplicaMiddleware::class, [
                $connection,
                $parameters['replica'],
                new Reference(AdapterRegistry::class),
                $parameters['fallback'],
                $parameters['disabled'],
                empty($parameters['catch']) ? null : $parameters['catch'],
            ]);

            $definition->addTag('runopencode.query.middleware', [
                'alias' => $alias,
            ]);

            $container->setDefinition($id, $definition);
        }
    }
}
