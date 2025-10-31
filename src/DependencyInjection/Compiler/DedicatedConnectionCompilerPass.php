<?php

namespace Tourze\RedisDedicatedConnectionBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;
use Tourze\RedisDedicatedConnectionBundle\Exception\InvalidChannelException;

/**
 * Redis 连接创建编译器传递
 * 负责处理所有需要专用 Redis 连接的服务
 */
class DedicatedConnectionCompilerPass implements CompilerPassInterface
{
    use ConnectionCreationTrait;

    public function process(ContainerBuilder $container): void
    {
        if (!$container->hasDefinition('redis_dedicated_connection.factory')) {
            return;
        }

        // 处理通过标签定义的连接
        $this->processTaggedServices($container);
    }

    /**
     * 处理带有 redis.dedicated_connection 标签的服务
     */
    private function processTaggedServices(ContainerBuilder $container): void
    {
        $taggedServices = $this->getTaggedServiceIds($container, 'redis.dedicated_connection');

        foreach ($taggedServices as $id => $tags) {
            $definition = $container->getDefinition($id);

            foreach ($tags as $attributes) {
                $channel = $attributes['channel'] ?? null;
                if (null === $channel || '' === $channel) {
                    throw new InvalidChannelException(sprintf('The "redis.dedicated_connection" tag on service "%s" must have a "channel" attribute.', $id));
                }

                if (!is_string($channel)) {
                    throw new InvalidChannelException(sprintf('The "channel" attribute on service "%s" must be a string, %s given.', $id, get_debug_type($channel)));
                }

                $this->ensureConnectionService($container, $channel, $attributes);

                // 使用参数绑定机制让 Symfony 自动装配正确的 Redis 连接
                $connectionId = sprintf('redis.%s_connection', $channel);
                $definition->setBindings([
                    '$redis' => new Reference($connectionId),
                ]);
            }
        }
    }
}
