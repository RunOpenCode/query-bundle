<?php

declare(strict_types=1);

namespace RunOpenCode\Bundle\QueryBundle\CacheWarmer;

use Symfony\Component\Finder\Finder;
use Symfony\Component\HttpKernel\CacheWarmer\CacheWarmerInterface;
use Psr\Container\ContainerInterface;
use Twig\Cache\NullCache;
use Twig\Environment;
use Twig\Loader\ChainLoader;
use Twig\Loader\FilesystemLoader;
use Twig\Loader\LoaderInterface;
use Twig\Error\Error;

final readonly class TwigCacheWarmer implements CacheWarmerInterface
{
    /**
     * Cache warmer is optional, so we will inject container to lazy load services, if needed.
     *
     * @param ContainerInterface     $container Container.
     * @param list<non-empty-string> $patterns  File name patterns.
     */
    public function __construct(
        private ContainerInterface $container,
        private array              $patterns,
    ) {
        // noop.
    }

    /**
     * {@inheritdoc}
     */
    public function warmUp(string $cacheDir, ?string $buildDir = null): array
    {
        /** @var Environment $twig */
        $twig = $this->container->get('runopencode.query.twig');

        if ($twig->getCache() instanceof NullCache) {
            return [];
        }

        $loaders   = $this->getLoaders($twig->getLoader());
        $scheduled = [];

        foreach ($loaders as $loader) {
            $templates = $this->getTemplates($loader);

            foreach ($templates as $template) {
                $scheduled[$template] = $template;
            }
        }

        foreach ($scheduled as $current) {
            try {
                $twig->load($current);
            } catch (Error) {
                // noop.
            }
        }

        return [];
    }

    /**
     * {@inheritdoc}
     */
    public function isOptional(): bool
    {
        return true;
    }

    /**
     * Extract filesystem loaders from environment.
     *
     * @param LoaderInterface $loader Loader to traverse.
     *
     * @return iterable<FilesystemLoader>
     */
    private function getLoaders(LoaderInterface $loader): iterable
    {
        if ($loader instanceof FilesystemLoader) {
            yield $loader;
        }

        if ($loader instanceof ChainLoader) {
            foreach ($loader->getLoaders() as $current) {
                yield from $this->getLoaders($current);
            }
        }
    }

    /**
     * Get templates registered in loader.
     *
     *
     * @return iterable<non-empty-string>
     */
    private function getTemplates(FilesystemLoader $loader): iterable
    {
        $namespaces = $loader->getNamespaces();

        foreach ($namespaces as $namespace) {
            $paths = $loader->getPaths($namespace);

            foreach ($paths as $path) {
                $finder = Finder::create()->files()->followLinks()->name($this->patterns)->in($path);

                foreach ($finder as $file) {
                    /** @var non-empty-string $relativePath */
                    $relativePath = $file->getRelativePathname();

                    if (FilesystemLoader::MAIN_NAMESPACE === $namespace) {
                        yield $relativePath;
                        continue;
                    }

                    yield \sprintf('@%s/%s', $namespace, $relativePath);
                }
            }
        }
    }
}
