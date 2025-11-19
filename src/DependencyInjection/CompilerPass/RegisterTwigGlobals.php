<?php

declare(strict_types=1);

namespace RunOpenCode\Bundle\QueryBundle\DependencyInjection\CompilerPass;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

/**
 * @phpstan-import-type TwigGlobals from \RunOpenCode\Bundle\QueryBundle\QueryBundle
 */
final readonly class RegisterTwigGlobals implements CompilerPassInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container): void
    {
        if (!$container->hasDefinition('runopencode.query.twig')) {
            return;
        }

        /** @var TwigGlobals $globals */
        $globals = $container->getParameter('.runopencode.query.configuration.twig.globals');
        $twig    = $container->getDefinition('runopencode.query.twig');


        foreach ($globals as $name => $value) {
            if ('service' === ($value['type'] ?? null)) {
                $twig->addMethodCall('addGlobal', [$name, new Reference($value['id'])]);
                continue;
            }

            $twig->addMethodCall('addGlobal', [$name, $value['value']]);
        }
    }
}
