<?php

namespace Tourze\RedisDedicatedConnectionBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;
use Tourze\RedisDedicatedConnectionBundle\Exception\InvalidChannelException;

/**
 * 处理通过参数 redis.connection_channel 定义连接通道的服务
 *
 * 使用示例：
 * 配置示例：
 * services:
 *   App\Service\CacheService:
 *     arguments:
 *       $redis: '@redis'
 *     calls:
 *       - [setRedis, ['@redis']
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

        $taggedServices = $this->getTaggedServiceIds($container, 'redis.connection_channel');

        foreach ($taggedServices as $id => $tags) {
            if (!$container->hasDefinition($id)) {
                continue;
            }

            $definition = $container->getDefinition($id);

            foreach ($tags as $attributes) {
                $channel = $attributes['channel'] ?? null;
                if (null === $channel || '' === $channel) {
                    throw new InvalidChannelException(sprintf('The "redis.connection_channel" tag on service "%s" must have a "channel" attribute.', $id));
                }

                if (!is_string($channel)) {
                    throw new InvalidChannelException(sprintf('The "channel" attribute on service "%s" must be a string, %s given.', $id, get_debug_type($channel)));
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
    private function processConstructorArguments(ContainerBuilder $container, Definition $definition, string $channel): void
    {
        $connectionId = sprintf('redis.%s_connection', $channel);
        $arguments = $definition->getArguments();

        $updatedArguments = [];
        foreach ($arguments as $argument) {
            $updatedArguments[] = $this->replaceRedisReference($argument, $connectionId);
        }

        $definition->setArguments($updatedArguments);
    }

    /**
     * 处理方法调用中的 Redis 连接
     */
    private function processMethodCalls(ContainerBuilder $container, Definition $definition, string $channel): void
    {
        $connectionId = sprintf('redis.%s_connection', $channel);
        $methodCalls = $definition->getMethodCalls();

        $updatedCalls = [];
        foreach ($methodCalls as $call) {
            if (!is_array($call) || count($call) !== 2 || !isset($call[0], $call[1]) || !is_string($call[0]) || !is_array($call[1])) {
                continue;
            }

            /** @var array{0: string, 1: array<int, mixed>} $validCall */
            $validCall = $call;
            $updatedCall = $this->replaceRedisReferencesInCall($validCall, $connectionId);
            $updatedCalls[] = $updatedCall;
        }

        $definition->setMethodCalls($updatedCalls);
    }

    /**
     * 替换单个方法调用中的 Redis 引用
     * @param array{0: string, 1: array<int, mixed>} $call
     * @return array{0: string, 1: array<int, mixed>}
     */
    private function replaceRedisReferencesInCall(array $call, string $connectionId): array
    {
        [$method, $arguments] = $call;

        $updatedArguments = [];
        foreach ($arguments as $argument) {
            $updatedArguments[] = $this->replaceRedisReference($argument, $connectionId);
        }

        return [$method, $updatedArguments];
    }

    /**
     * 替换单个参数中的 Redis 引用
     * @param mixed $argument
     * @return mixed
     */
    private function replaceRedisReference($argument, string $connectionId): mixed
    {
        if (!$argument instanceof Reference) {
            return $argument;
        }

        $refId = (string) $argument;
        if ($this->isRedisReference($refId)) {
            return new Reference($connectionId);
        }

        return $argument;
    }

    /**
     * 检查是否为 Redis 服务引用
     */
    private function isRedisReference(string $refId): bool
    {
        return in_array($refId, ['redis', 'Redis', 'redis.default'], true);
    }
}
