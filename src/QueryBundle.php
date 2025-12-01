<?php

declare(strict_types=1);

namespace RunOpenCode\Bundle\QueryBundle;

use RunOpenCode\Component\Query\Contract\Middleware\MiddlewareInterface;
use RunOpenCode\Bundle\QueryBundle\DependencyInjection\CompilerPass;
use RunOpenCode\Component\Query\Replica\FallbackStrategy;
use Symfony\Component\Config\Definition\Configurator\DefinitionConfigurator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpKernel\Bundle\AbstractBundle;

/**
 * @phpstan-type TwigGlobal = array{
 *     id: non-empty-string,
 *     value: mixed,
 *     type?: string,
 * }
 *
 * @phpstan-type TwigGlobals = array<non-empty-string, TwigGlobal>
 *
 * @phpstan-type TwigConfig = array{
 *     autoescape_service: non-empty-string|null,
 *     autoescape_service_method: non-empty-string|null,
 *     cache: non-empty-string|null,
 *     charset: non-empty-string,
 *     debug: bool,
 *     strict_variables: bool,
 *     auto_reload?: bool,
 *     optimizations?: integer,
 *     paths: array<non-empty-string, non-empty-string|null>,
 *     default_path: non-empty-string,
 *     file_name_pattern: non-empty-string[],
 *     globals?: TwigGlobals,
 * }
 *
 * @phpstan-type ReplicaConfig = array{
 *     replica: non-empty-list<non-empty-string>,
 *     fallback: FallbackStrategy,
 *     disabled: boolean,
 * }
 *
 * @phpstan-type Config = array{
 *     cache_pool: non-empty-string|null,
 *     twig: TwigConfig,
 *     middlewares: array{
 *         stack: non-empty-list<non-empty-string|class-string<MiddlewareInterface>>,
 *         replica?: array<non-empty-string, ReplicaConfig>,
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

        $container
            ->parameters()
            ->set('.runopencode.query.configuration.twig', [
                'autoescape'       => false,
                'cache'            => $config['twig']['cache'],
                'charset'          => $config['twig']['charset'],
                'debug'            => $config['twig']['debug'],
                'strict_variables' => $config['twig']['strict_variables'],
                'auto_reload'      => $config['twig']['auto_reload'] ?? null,
                'optimizations'    => $config['twig']['optimizations'] ?? -1,
            ])
            ->set('.runopencode.query.configuration.twig.default_path', $config['twig']['default_path'])
            ->set('.runopencode.query.configuration.twig.paths', $config['twig']['paths'])
            ->set('.runopencode.query.configuration.twig.file_name_pattern', $config['twig']['file_name_pattern'])
            ->set('.runopencode.query.configuration.twig.globals', $config['twig']['globals'] ?? [])
            ->set('.runopencode.query.configuration.cache_pool', $config['cache_pool'])
            ->set('.runopencode.query.configuration.middlewares.replica', $config['middlewares']['stack'])
            ->set('.runopencode.query.configuration.middlewares.stack', $config['middlewares']['replica'] ?? []);
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
        $container->addCompilerPass(new CompilerPass\ConfigureCacheMiddleware());
        $container->addCompilerPass(new CompilerPass\ConfigureReplicaMiddleware());
        $container->addCompilerPass(new CompilerPass\ConfigureMiddlewareStack());
    }
}
