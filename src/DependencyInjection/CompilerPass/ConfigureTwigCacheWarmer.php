<?php

declare(strict_types=1);

namespace RunOpenCode\Bundle\QueryBundle\DependencyInjection\CompilerPass;

use RunOpenCode\Bundle\QueryBundle\CacheWarmer\TwigCacheWarmer;
use RunOpenCode\Bundle\QueryBundle\QueryBundle;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * @phpstan-import-type TwigParserConfig from QueryBundle
 */
final readonly class ConfigureTwigCacheWarmer implements CompilerPassInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container): void
    {
        if (
            !$container->hasDefinition(TwigCacheWarmer::class)
            ||
            !$container->hasParameter('.runopencode.query.configuration.parser.twig')
        ) {
            return;
        }
        
        /** @var TwigParserConfig $configuration */
        $configuration = $container->getParameter('.runopencode.query.configuration.parser.twig');

        $container
            ->getDefinition(TwigCacheWarmer::class)
            ->setArgument('$namePatterns', $configuration['pattern']);
    }
}
