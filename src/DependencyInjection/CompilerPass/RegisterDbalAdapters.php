<?php

declare(strict_types=1);

namespace RunOpenCode\Bundle\QueryBundle\DependencyInjection\CompilerPass;

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

        /** @var array<non-empty-string, non-empty-string> $connections */
        $connections = $container->getParameter('doctrine.connections');

        /** @var non-empty-string $connection */
        foreach ($connections as $alias => $connection) {
            $definition = new Definition(Adapter::class, [
                $alias,
                new Reference($connection),
            ]);

            $definition->addTag('runopencode.query.adapter');

            $container->setDefinition(
                \sprintf('runopencode.query.adapter.%s', $alias),
                $definition,
            );
        }
    }
}
