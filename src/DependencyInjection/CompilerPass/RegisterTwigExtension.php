<?php

declare(strict_types=1);

namespace RunOpenCode\Bundle\QueryBundle\DependencyInjection\CompilerPass;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Registers Twig extensions for query Twig environment instance.
 */
final readonly class RegisterTwigExtension implements CompilerPassInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container): void
    {
        if (!$container->hasDefinition('runopencode.query.twig')) {
            return;
        }

        $extensions = $container->findTaggedServiceIds('runopencode.query.twig.extension');

        // No extensions to register.
        if (empty($extensions)) {
            return;
        }

        $twig = $container->getDefinition('runopencode.query.twig');

        // Extensions must always be registered before everything else.
        // For instance, global variable definitions must be registered
        // afterward. If not, the globals from the extensions will never
        // be registered.
        $calls = [
            ...\array_map(static fn(string $id): array => ['addExtension', [new Reference($id)]], \array_keys($extensions)),
            ...$twig->getMethodCalls(),
        ];

        $twig->setMethodCalls($calls);
    }
}
