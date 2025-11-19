<?php

declare(strict_types=1);

namespace RunOpenCode\Component\QueryBundle\DependencyInjection\CompilerPass;

use RunOpenCode\Component\Query\Cache\CacheMiddleware;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

final readonly class ConfigureCacheMiddleware implements CompilerPassInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container): void
    {
        if (!$container->hasDefinition(CacheMiddleware::class)) {
            return;
        }

        /** @var non-empty-string|null $pool */
        $pool = $container->getParameter('.runopencode.query.configuration.cache_pool');

        if (null === $pool) {
            return;
        }

        $container
            ->getDefinition(CacheMiddleware::class)
            ->setArgument('$cache', new Reference($pool));
    }
}
