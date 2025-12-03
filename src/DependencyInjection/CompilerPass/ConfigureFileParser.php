<?php

declare(strict_types=1);

namespace RunOpenCode\Bundle\QueryBundle\DependencyInjection\CompilerPass;

use RunOpenCode\Component\Query\Parser\FileParser;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

use function RunOpenCode\Component\Query\to_regex;

/**
 * @phpstan-type FileParserConfig = array{
 *     pattern: list<non-empty-string>,
 *     paths: list<array{non-empty-string, non-empty-string}>,
 * }
 */
final readonly class ConfigureFileParser implements CompilerPassInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container): void
    {
        if (
            !$container->hasDefinition(FileParser::class)
            ||
            !$container->hasParameter('.runopencode.query.configuration.parser.file')
        ) {
            return;
        }

        /** @var FileParserConfig $configuration */
        $configuration = $container->getParameter('.runopencode.query.configuration.parser.file');

        $container
            ->getDefinition(FileParser::class)
            ->setArgument('$patterns', \array_map(static fn(string $pattern): string => to_regex($pattern), $configuration['pattern']));
    }
}
