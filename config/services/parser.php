<?php

declare(strict_types=1);

use RunOpenCode\Component\Query\Parser\FileParser;
use RunOpenCode\Component\Query\Parser\ParserRegistry;
use RunOpenCode\Component\Query\Parser\TwigParser;
use RunOpenCode\Component\Query\Parser\VoidParser;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

use function Symfony\Component\DependencyInjection\Loader\Configurator\service;
use function Symfony\Component\DependencyInjection\Loader\Configurator\tagged_iterator;

return static function(ContainerConfigurator $container): void {
    $configurator = $container->services();

    $configurator
        ->set(FileParser::class)
        ->tag('runopencode.query.parser', [
            'alias' => FileParser::NAME,
            'priority' => 1000,
        ]);

    $configurator
        ->set(TwigParser::class)
        ->arg('$twig', service('runopencode.query.twig'))
        ->tag('runopencode.query.parser', [
            'alias'    => TwigParser::NAME,
            'priority' => 500,
        ]);

    $configurator
        ->set(VoidParser::class)
        ->tag('runopencode.query.parser', [
            'alias'    => VoidParser::NAME,
            'priority' => 0,
        ]);

    $configurator
        ->set(ParserRegistry::class)
        ->args([
            tagged_iterator('runopencode.query.parser'),
        ]);

};
