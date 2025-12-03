<?php

declare(strict_types=1);

namespace RunOpenCode\Bundle\QueryBundle;

use Psr\Log\LogLevel;
use RunOpenCode\Bundle\QueryBundle\DependencyInjection\CompilerPass;
use RunOpenCode\Component\Query\Contract\Middleware\QueryMiddlewareInterface;
use RunOpenCode\Component\Query\Contract\Middleware\StatementMiddlewareInterface;
use RunOpenCode\Component\Query\Contract\Middleware\TransactionMiddlewareInterface;
use RunOpenCode\Component\Query\Replica\FallbackStrategy;
use Symfony\Component\Config\Definition\Configurator\DefinitionConfigurator;
use Symfony\Component\Config\Resource\FileExistenceResource;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpKernel\Bundle\AbstractBundle;
use Twig\Loader\FilesystemLoader;

/**
 * @phpstan-type Middleware = QueryMiddlewareInterface|StatementMiddlewareInterface|TransactionMiddlewareInterface
 *
 * @phpstan-type FileParserConfig = array{
 *     pattern: list<non-empty-string>,
 * }
 *
 * @phpstan-type TwigGlobal = array{
 *     id: non-empty-string,
 *     value: mixed,
 *     type?: string,
 * }
 *
 * @phpstan-type TwigParserGlobals = array<non-empty-string, TwigGlobal>
 *
 * @phpstan-type TwigParserConfig = array{
 *     cache: non-empty-string|null,
 *     charset: non-empty-string,
 *     debug: bool,
 *     strict_variables: bool,
 *     auto_reload?: bool,
 *     optimizations?: integer,
 *     pattern: list<non-empty-string>,
 *     globals?: TwigParserGlobals,
 * }
 *
 * @phpstan-type ReplicaMiddlewareConfig = array{
 *     replica: non-empty-list<non-empty-string>,
 *     fallback: FallbackStrategy,
 *     disabled: boolean,
 *     catch: list<class-string<\Exception>>
 * }
 *
 * @phpstan-type RetryMiddlewareConfig = array{
 *     catch: list<class-string<\Exception>>
 * }
 *
 * @phpstan-type SlowMiddlewareConfig = array{
 *     logger: non-empty-string|null,
 *     level: LogLevel::*,
 *     threshold: positive-int,
 *     always: bool,
 * }
 *
 * @phpstan-type CacheMiddlewareConfig = array{
 *     cache_pool: non-empty-string|null
 * }
 *
 * @phpstan-type Config = array{
 *     default_connection: non-empty-string|null,
 *     default_query_path: non-empty-string|null,
 *     query_paths: array<non-empty-string, non-empty-string|null>,
 *     parser: array{
 *         file: FileParserConfig,
 *         twig: TwigParserConfig,
 *     },
 *     middlewares: array{
 *         stack: non-empty-list<non-empty-string|class-string<Middleware>>,
 *         replica?: array<non-empty-string, ReplicaMiddlewareConfig>,
 *         retry: RetryMiddlewareConfig,
 *         slow: SlowMiddlewareConfig,
 *         cache: CacheMiddlewareConfig,
 *     }
 * }
 */
final class QueryBundle extends AbstractBundle
{
    protected string $extensionAlias = 'runopencode_query';

    /**
     * {@inheritdoc}
     */
    public function configure(DefinitionConfigurator $definition): void
    {
        $definition->import('../config/definition/*.php');
    }

    /**
     * {@inheritdoc}
     *
     * @param Config $config
     */
    public function loadExtension(array $config, ContainerConfigurator $container, ContainerBuilder $builder): void
    {
        $container->import('../config/services/*.php');

        $paths = $this->processPaths($config['default_query_path'], $config['query_paths'], $builder);

        $container
            ->parameters()
            ->set('runopencode.query.default_connection', $config['default_connection'])
            ->set('runopencode.query.query_paths', $paths)
            ->set('.runopencode.query.configuration.parser.twig', [
                'autoescape'       => false,
                'cache'            => $config['parser']['twig']['cache'],
                'charset'          => $config['parser']['twig']['charset'],
                'debug'            => $config['parser']['twig']['debug'],
                'strict_variables' => $config['parser']['twig']['strict_variables'],
                'auto_reload'      => $config['parser']['twig']['auto_reload'] ?? null,
                'optimizations'    => $config['parser']['twig']['optimizations'] ?? -1,
                'pattern'          => $config['parser']['twig']['pattern'],
                'globals'          => $config['parser']['twig']['globals'] ?? [],
            ])
            ->set('.runopencode.query.configuration.parser.file', [
                'pattern' => $config['parser']['file']['pattern'],
            ])
            ->set('.runopencode.query.configuration.middlewares.replica', $config['middlewares']['replica'] ?? [])
            ->set('.runopencode.query.configuration.middlewares.retry', $config['middlewares']['retry'])
            ->set('.runopencode.query.configuration.middlewares.slow', $config['middlewares']['slow'])
            ->set('.runopencode.query.configuration.middlewares.cache', $config['middlewares']['cache'])
            ->set('.runopencode.query.configuration.middlewares.stack', $config['middlewares']['stack']);
    }

    /**
     * {@inheritdoc}
     */
    public function build(ContainerBuilder $container): void
    {
        parent::build($container);
        $container->addCompilerPass(new CompilerPass\RegisterDbalAdapters());
        $container->addCompilerPass(new CompilerPass\ConfigureTwigEnvironment());
        $container->addCompilerPass(new CompilerPass\ConfigureTwigLoader());
        $container->addCompilerPass(new CompilerPass\RegisterTwigExtension());
        $container->addCompilerPass(new CompilerPass\ConfigureTwigCacheWarmer());
        $container->addCompilerPass(new CompilerPass\RegisterTwigGlobals());
        $container->addCompilerPass(new CompilerPass\ConfigureFileParser());
        $container->addCompilerPass(new CompilerPass\ConfigureCacheMiddleware());
        $container->addCompilerPass(new CompilerPass\ConfigureReplicaMiddleware());
        $container->addCompilerPass(new CompilerPass\ConfigureMiddlewareStack());
        $container->addCompilerPass(new CompilerPass\ConfigureTwigParser());
        $container->addCompilerPass(new CompilerPass\ConfigureSlowExecutionMiddleware());
        $container->addCompilerPass(new CompilerPass\ConfigureRetryMiddleware());
    }

    /**
     * Process registered paths for parsers.
     *
     * @param non-empty-string|null                          $defaultPath
     * @param array<non-empty-string, non-empty-string|null> $paths
     * @param ContainerBuilder                               $builder
     *
     * @return list<array{non-empty-string, non-empty-string}>
     */
    private function processPaths(?string $defaultPath, array $paths, ContainerBuilder $builder): array
    {
        $parameterBag = $builder->getParameterBag();
        $defaultPath  = $parameterBag->resolveValue($defaultPath);
        $paths        = $parameterBag->resolveValue($paths);

        /**
         * @var list<array{non-empty-string, non-empty-string}> $processed
         */
        $processed = [];

        /**
         * First, we process default path.
         *
         * @var non-empty-string|null $defaultPath
         */
        if (null !== $defaultPath) {
            $builder->addResource(new FileExistenceResource($defaultPath));

            if (\is_dir($defaultPath)) {
                $processed[] = [$defaultPath, FilesystemLoader::MAIN_NAMESPACE];
            }
        }

        /**
         * Then we process our own configured paths.
         *
         * @var array<non-empty-string, non-empty-string|null> $paths
         */
        foreach ($paths as $path => $namespace) {
            $builder->addResource(new FileExistenceResource($path));

            if (!\is_dir($path)) {
                continue;
            }

            $processed[] = [$path, $namespace ?? FilesystemLoader::MAIN_NAMESPACE];
        }

        /**
         * Then, a bundle paths, giving a priority to the overrides.
         *
         * @var array<non-empty-string, class-string> $bundles
         */
        $bundles = $builder->getParameter('kernel.bundles');
        /** @var non-empty-string $projectDir */
        $projectDir = $builder->getParameter('kernel.project_dir');

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
                $builder->addResource(new FileExistenceResource($directory));

                if (!\is_dir($directory)) {
                    continue;
                }

                /**
                 * @var  non-empty-string $directory
                 * @var non-empty-string  $namespace
                 */
                $processed[] = [$directory, $namespace];
            }
        }

        return $processed;
    }
}
