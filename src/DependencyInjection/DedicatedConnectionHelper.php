<?php

namespace Tourze\RedisDedicatedConnectionBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;
use Tourze\RedisDedicatedConnectionBundle\Exception\InvalidChannelException;

/**
 * 辅助类，提供便捷的方法来配置专用 Redis 连接
 */
class DedicatedConnectionHelper
{
    /**
     * 为服务添加专用连接标签
     *
     * @param Definition|string     $definition 服务定义或服务 ID
     * @param string                $channel    连接通道名称
     * @param ContainerBuilder|null $container  当传入服务 ID 时必须提供
     */
    public static function addDedicatedConnection(Definition|string $definition, string $channel, ?ContainerBuilder $container = null): Definition
    {
        if (is_string($definition)) {
            if (null === $container) {
                throw new InvalidChannelException('Container must be provided when using service ID');
            }
            $definition = $container->getDefinition($definition);
        }

        $definition->addTag('redis.dedicated_connection', ['channel' => $channel]);

        return $definition;
    }

    /**
     * 为服务添加连接通道标签（用于更细粒度的控制）
     *
     * @param Definition|string     $definition 服务定义或服务 ID
     * @param string                $channel    连接通道名称
     * @param ContainerBuilder|null $container  当传入服务 ID 时必须提供
     */
    public static function addConnectionChannel(Definition|string $definition, string $channel, ?ContainerBuilder $container = null): Definition
    {
        if (is_string($definition)) {
            if (null === $container) {
                throw new InvalidChannelException('Container must be provided when using service ID');
            }
            $definition = $container->getDefinition($definition);
        }

        $definition->addTag('redis.connection_channel', ['channel' => $channel]);

        return $definition;
    }

    /**
     * 检查容器中是否已存在指定通道的连接
     */
    public static function hasConnection(ContainerBuilder $container, string $channel): bool
    {
        $connectionId = sprintf('redis.%s_connection', $channel);

        return $container->hasDefinition($connectionId) || $container->hasAlias($connectionId);
    }

    /**
     * 获取连接服务 ID
     */
    public static function getConnectionId(string $channel): string
    {
        return sprintf('redis.%s_connection', $channel);
    }

    /**
     * 为服务配置多个专用连接
     *
     * @param Definition|string     $definition 服务定义或服务 ID
     * @param array<string>         $channels   通道名称数组
     * @param ContainerBuilder|null $container  当传入服务 ID 时必须提供
     */
    public static function addMultipleDedicatedConnections(Definition|string $definition, array $channels, ?ContainerBuilder $container = null): Definition
    {
        if (is_string($definition)) {
            if (null === $container) {
                throw new InvalidChannelException('Container must be provided when using service ID');
            }
            $definition = $container->getDefinition($definition);
        }

        foreach ($channels as $channel) {
            $definition->addTag('redis.dedicated_connection', ['channel' => $channel]);
        }

        return $definition;
    }

    /**
     * 创建一个带有专用连接的服务定义
     *
     * @param string         $class               服务类名
     * @param string         $channel             连接通道名称
     * @param array<mixed>   $additionalArguments 额外的构造函数参数
     */
    public static function createServiceWithConnection(string $class, string $channel, array $additionalArguments = []): Definition
    {
        $definition = new Definition($class);

        // 添加 Redis 连接作为第一个参数
        $arguments = [self::createConnectionReference($channel)];

        // 添加额外的参数
        foreach ($additionalArguments as $argument) {
            $arguments[] = $argument;
        }

        $definition->setArguments($arguments);
        $definition->addTag('redis.dedicated_connection', ['channel' => $channel]);

        return $definition;
    }

    /**
     * 创建一个专用连接的引用
     *
     * @param string $channel 连接通道名称
     */
    public static function createConnectionReference(string $channel): Reference
    {
        return new Reference(sprintf('redis.%s_connection', $channel));
    }
}
