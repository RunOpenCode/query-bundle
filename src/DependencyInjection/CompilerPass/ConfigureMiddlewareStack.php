<?php

declare(strict_types=1);

namespace RunOpenCode\Bundle\QueryBundle\DependencyInjection\CompilerPass;

use RunOpenCode\Component\Query\Contract\Middleware\MiddlewareInterface;
use RunOpenCode\Component\Query\Middleware\MiddlewareChain;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException;
use Symfony\Component\DependencyInjection\Reference;

final readonly class ConfigureMiddlewareStack implements CompilerPassInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container): void
    {
        if (!$container->hasDefinition(MiddlewareChain::class)) {
            return;
        }

        /** @var non-empty-list<non-empty-string|class-string<MiddlewareInterface>> $stack */
        $stack       = $container->getParameter('.runopencode.query.configuration.middlewares.stack');
        $middlewares = \iterator_to_array($this->getTaggedMiddlewares($container));
        $resolved    = \array_map(static function(string $middleware) use ($container, $middlewares): Reference {
            if (isset($middlewares[$middleware])) {
                return $middlewares[$middleware];
            }

            if ($container->hasDefinition($middleware)) {
                return new Reference($middleware);
            }

            throw new ServiceNotFoundException($middleware, msg: \sprintf(
                'Middleware stack defines service "%s" which does not exist.',
                $middleware,
            ));
        }, $stack);

        $container
            ->getDefinition(MiddlewareChain::class)
            ->setArgument('$middlewares', $resolved);
    }

    /**
     * Get tagged middlewares from container.
     *
     * @return iterable<non-empty-string|class-string<MiddlewareInterface>, Reference>
     */
    private function getTaggedMiddlewares(ContainerBuilder $container): iterable
    {
        $tagged = $container->findTaggedServiceIds('runopencode.query.middleware');

        /**
         * @var list<array{ alias?: non-empty-string }> $attributes
         * @var non-empty-string                        $id
         */
        foreach ($tagged as $id => $attributes) {
            foreach ($attributes as $attribute) {
                if (isset($attribute['alias'])) {
                    yield $attribute['alias'] => new Reference($id);
                    continue 2;
                }
            }

            yield $id => new Reference($id);
        }
    }
}
