<?php

declare(strict_types=1);

namespace RunOpenCode\Bundle\QueryBundle\DependencyInjection\CompilerPass;

use Symfony\Component\Config\Resource\FileExistenceResource;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Twig\Loader\FilesystemLoader;

final readonly class ConfigureTwigLoader implements CompilerPassInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container): void
    {
        if (!$container->hasDefinition('runopencode.query.twig.loader.filesystem')) {
            return;
        }

        $loader      = $container->getDefinition('runopencode.query.twig.loader.filesystem');
        $defaultPath = $container->getParameter('.runopencode.query.configuration.twig.default_path');
        $paths       = $container->getParameter('.runopencode.query.configuration.twig.paths');
        $bundles     = $container->getParameter('kernel.bundles');
        $projectDir  = $container->getParameter('kernel.project_dir');

        \assert(\is_string($projectDir));

        $loader->addMethodCall('addPath', [$defaultPath, FilesystemLoader::MAIN_NAMESPACE]);

        /** @var array<string, string|null> $paths */
        foreach ($paths as $path => $namespace) {
            $loader->addMethodCall('addPath', [$path, $namespace ?? FilesystemLoader::MAIN_NAMESPACE]);
        }

        /** @var array<string, class-string> $bundles */
        foreach ($bundles as $bundle => $class) {
            $namespace = \str_ends_with($bundle, 'Bundle') ? \substr($bundle, 0, -6) : $bundle;
            $location  = \dirname(new \ReflectionClass($class)->getFileName() ?: throw new \RuntimeException(\sprintf(
                'Unable to locate bundle "%s" root directory.',
                $class
            )));

            $locations = [
                \sprintf('%s/query/bundles/%s', $projectDir, $bundle),
                \sprintf('%s/Resources/query', $location),
                \sprintf('%s/query', $location),
            ];

            foreach ($locations as $directory) {
                if (\is_dir($directory)) {
                    $loader->addMethodCall('addPath', [$directory, $namespace]);
                }

                $container->addResource(new FileExistenceResource($directory));
            }
        }
    }
}
