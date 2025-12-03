<?php

declare(strict_types=1);

namespace RunOpenCode\Bundle\QueryBundle\DependencyInjection\CompilerPass;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

final readonly class ConfigureTwigLoader implements CompilerPassInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container): void
    {
        if (
            !$container->hasDefinition('runopencode.query.twig.loader.filesystem')
            ||
            !$container->hasParameter('runopencode.query.query_paths')
        ) {
            return;
        }

        /**
         * @var list<array{non-empty-string, non-empty-string}> $paths
         */
        $paths  = $container->getParameter('runopencode.query.query_paths');
        $loader = $container->getDefinition('runopencode.query.twig.loader.filesystem');

        foreach ($paths as $path) {
            $loader->addMethodCall('addPath', $path);
        }
    }
}
