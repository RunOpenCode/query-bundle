<?php

declare(strict_types=1);

namespace RunOpenCode\Bundle\QueryBundle\DependencyInjection\CompilerPass;

use RunOpenCode\Bundle\QueryBundle\QueryBundle;
use RunOpenCode\Component\Query\Cache\CacheMiddleware;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

/**
 * @phpstan-import-type CacheMiddlewareConfig from QueryBundle
 */
final readonly class ConfigureCacheMiddleware implements CompilerPassInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container): void
    {
        if (
            !$container->hasDefinition(CacheMiddleware::class)
            ||
            !$container->hasParameter('.runopencode.query.configuration.middlewares.cache')
        ) {
            return;
        }

        /** @var CacheMiddlewareConfig $configuration */
        $configuration = $container->getParameter('.runopencode.query.configuration.middlewares.cache');

        $container
            ->getDefinition(CacheMiddleware::class)
            ->setArgument('$cache', $configuration['cache_pool'] ? new Reference($configuration['cache_pool']) : null);
    }
}
