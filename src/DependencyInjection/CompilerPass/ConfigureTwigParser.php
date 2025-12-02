<?php

declare(strict_types=1);

namespace RunOpenCode\Bundle\QueryBundle\DependencyInjection\CompilerPass;

use RunOpenCode\Bundle\QueryBundle\QueryBundle;
use RunOpenCode\Component\Query\Parser\TwigParser;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

use function RunOpenCode\Component\Query\to_regex;

/**
 * @phpstan-import-type TwigParserConfig from QueryBundle
 */
final readonly class ConfigureTwigParser implements CompilerPassInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container): void
    {
        if (
            !$container->hasDefinition(TwigParser::class)
            ||
            !$container->hasParameter('.runopencode.query.configuration.parser.twig')
        ) {
            return;
        }

        /** @var TwigParserConfig $configuration */
        $configuration = $container->getParameter('.runopencode.query.configuration.parser.twig');

        $container
            ->getDefinition(TwigParser::class)
            ->setArgument('$patterns', \array_map(static fn(string $pattern): string => to_regex($pattern), $configuration['pattern']));
    }
}
