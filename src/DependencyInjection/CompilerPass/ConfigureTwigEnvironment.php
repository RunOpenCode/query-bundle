<?php

declare(strict_types=1);

namespace RunOpenCode\Bundle\QueryBundle\DependencyInjection\CompilerPass;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

final readonly class ConfigureTwigEnvironment implements CompilerPassInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container): void
    {
        if (!$container->hasDefinition('runopencode.query.twig')) {
            return;
        }

        $options = $container->getParameter('.runopencode.query.configuration.twig');

        $container
            ->getDefinition('runopencode.query.twig')
            ->setArgument('$options', $options);
    }
}
