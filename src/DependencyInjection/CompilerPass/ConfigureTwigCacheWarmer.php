<?php

declare(strict_types=1);

namespace RunOpenCode\Bundle\QueryBundle\DependencyInjection\CompilerPass;

use RunOpenCode\Bundle\QueryBundle\CacheWarmer\TwigCacheWarmer;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

final readonly class ConfigureTwigCacheWarmer implements CompilerPassInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container): void
    {
        if (!$container->hasDefinition(TwigCacheWarmer::class)) {
            return;
        }

        $container
            ->getDefinition(TwigCacheWarmer::class)
            ->setArgument('$namePatterns', $container->getParameter('.runopencode.query.configuration.twig.file_name_pattern'));
    }
}
