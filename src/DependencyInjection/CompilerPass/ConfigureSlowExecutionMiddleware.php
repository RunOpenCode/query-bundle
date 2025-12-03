<?php

declare(strict_types=1);

namespace RunOpenCode\Bundle\QueryBundle\DependencyInjection\CompilerPass;

use RunOpenCode\Bundle\QueryBundle\QueryBundle;
use RunOpenCode\Component\Query\Monitor\SlowExecutionMiddleware;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

/**
 * @phpstan-import-type SlowMiddlewareConfig from QueryBundle
 */
final readonly class ConfigureSlowExecutionMiddleware implements CompilerPassInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container): void
    {
        if (
            !$container->hasDefinition(SlowExecutionMiddleware::class)
            ||
            !$container->getParameter('.runopencode.query.configuration.middlewares.slow')
        ) {
            return;
        }

        /** @var SlowMiddlewareConfig $configuration */
        $configuration = $container->getParameter('.runopencode.query.configuration.middlewares.slow');

        $container
            ->getDefinition(SlowExecutionMiddleware::class)
            ->setArgument('$logger', new Reference($configuration['logger'] ?? 'runopencode.query.null_logger'))
            ->setArgument('$level', $configuration['level'])
            ->setArgument('$threshold', $configuration['threshold'])
            ->setArgument('$always', $configuration['always']);
    }
}
