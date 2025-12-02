<?php

declare(strict_types=1);

namespace RunOpenCode\Bundle\QueryBundle\DependencyInjection\CompilerPass;

use RunOpenCode\Bundle\QueryBundle\QueryBundle;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * @phpstan-import-type TwigParserConfig from QueryBundle
 */
final readonly class ConfigureTwigEnvironment implements CompilerPassInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container): void
    {
        if (
            !$container->hasDefinition('runopencode.query.twig')
            ||
            !$container->hasParameter('.runopencode.query.configuration.parser.twig')
        ) {
            return;
        }

        /** @var TwigParserConfig $configuration */
        $configuration = $container->getParameter('.runopencode.query.configuration.parser.twig');

        $container
            ->getDefinition('runopencode.query.twig')
            ->setArgument('$options', [
                'cache'            => $configuration['cache'],
                'charset'          => $configuration['charset'],
                'debug'            => $configuration['debug'],
                'strict_variables' => $configuration['strict_variables'],
                'auto_reload'      => $configuration['auto_reload'] ?? null,
                'optimizations'    => $configuration['optimizations'] ?? -1,
            ]);
    }
}
