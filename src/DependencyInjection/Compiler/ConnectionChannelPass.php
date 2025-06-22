<?php

namespace Tourze\RedisDedicatedConnectionBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

/**
 * 处理通过参数 redis.connection_channel 定义连接通道的服务
 * 
 * 使用示例：
 * ```yaml
 * services:
 *   App\Service\CacheService:
 *     arguments:
 *       $redis: '@redis'
 *     calls:
 *       - [setRedis, ['@redis']]
 *     tags:
 *       - { name: 'redis.connection_channel', channel: 'cache' }
 * ```
 */
class ConnectionChannelPass implements CompilerPassInterface
{
    use ConnectionCreationTrait;

    public function process(ContainerBuilder $container): void
    {
        if (!$container->hasDefinition('redis_dedicated_connection.factory')) {
            return;
        }

        $taggedServices = $container->findTaggedServiceIds('redis.connection_channel');

        foreach ($taggedServices as $id => $tags) {
            $definition = $container->getDefinition($id);

            foreach ($tags as $attributes) {
                $channel = $attributes['channel'] ?? null;
                if (!$channel) {
                    throw new \InvalidArgumentException(sprintf(
                        'The "redis.connection_channel" tag on service "%s" must have a "channel" attribute.',
                        $id
                    ));
                }

                $this->ensureConnectionService($container, $channel, $attributes);
                
                // 处理构造函数参数
                $this->processConstructorArguments($container, $definition, $channel);
                
                // 处理方法调用
                $this->processMethodCalls($container, $definition, $channel);
            }
        }
    }

    /**
     * 处理构造函数参数中的 Redis 连接
     */
    private function processConstructorArguments(ContainerBuilder $container, $definition, string $channel): void
    {
        $connectionId = sprintf('redis.%s_connection', $channel);
        $arguments = $definition->getArguments();
        $modified = false;

        foreach ($arguments as $index => $argument) {
            if ($argument instanceof Reference) {
                $refId = (string) $argument;
                // 替换通用的 redis 服务引用
                if (in_array($refId, ['redis', 'Redis', 'redis.default'])) {
                    $arguments[$index] = new Reference($connectionId);
                    $modified = true;
                }
            }
        }

        if ($modified) {
            $definition->setArguments($arguments);
        }
    }

    /**
     * 处理方法调用中的 Redis 连接
     */
    private function processMethodCalls(ContainerBuilder $container, $definition, string $channel): void
    {
        $connectionId = sprintf('redis.%s_connection', $channel);
        $methodCalls = $definition->getMethodCalls();
        $modified = false;

        foreach ($methodCalls as &$call) {
            [$method, $arguments] = $call;
            
            foreach ($arguments as $index => $argument) {
                if ($argument instanceof Reference) {
                    $refId = (string) $argument;
                    // 替换通用的 redis 服务引用
                    if (in_array($refId, ['redis', 'Redis', 'redis.default'])) {
                        $arguments[$index] = new Reference($connectionId);
                        $modified = true;
                    }
                }
            }
            
            if ($modified) {
                $call = [$method, $arguments];
            }
        }

        if ($modified) {
            $definition->setMethodCalls($methodCalls);
        }
    }
}