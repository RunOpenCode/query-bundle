<?php

declare(strict_types=1);

namespace RunOpenCode\Component\QueryBundle\DependencyInjection\CompilerPass;

use RunOpenCode\Component\Query\Doctrine\Dbal\Adapter;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

final readonly class RegisterDbalAdapters implements CompilerPassInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container): void
    {
        if (!$container->hasParameter('doctrine.connections')) {
            return;
        }

        /** @var list<non-empty-string> $connections */
        $connections = $container->getParameter('doctrine.connections');
        $default     = $container->getParameter('doctrine.default_connection');
        $sorted      = [
            $default,
            \array_filter($connections, static fn(string $connection): bool => $connection !== $default),
        ];

        /** @var non-empty-string $connection */
        foreach ($sorted as $connection) {
            $definition = new Definition(Adapter::class, [
                $connection,
                new Reference($connection),
            ]);

            $definition->addTag('runopencode.query.adapter');
        }
    }
}
