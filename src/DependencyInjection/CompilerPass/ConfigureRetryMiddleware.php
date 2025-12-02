<?php

declare(strict_types=1);

namespace RunOpenCode\Bundle\QueryBundle\DependencyInjection\CompilerPass;

use RunOpenCode\Bundle\QueryBundle\QueryBundle;
use RunOpenCode\Component\Query\Retry\RetryMiddleware;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * @phpstan-import-type RetryMiddlewareConfig from QueryBundle
 */
final readonly class ConfigureRetryMiddleware implements CompilerPassInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container): void
    {
        if (
            !$container->hasDefinition(RetryMiddleware::class)
            ||
            !$container->getParameter('.runopencode.query.configuration.middlewares.retry')
        ) {
            return;
        }

        /** @var RetryMiddlewareConfig $configuration */
        $configuration = $container->getParameter('.runopencode.query.configuration.middlewares.retry');

        $container
            ->getDefinition(RetryMiddleware::class)
            ->setArgument('$catch', $configuration['catch']);
    }
}
