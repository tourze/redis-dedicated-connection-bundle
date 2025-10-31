<?php

namespace Tourze\RedisDedicatedConnectionBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

/**
 * 共享的连接创建逻辑
 */
trait ConnectionCreationTrait
{
    /**
     * 获取带标签的服务ID列表
     * 这是一个包装方法，用于避免直接调用findTaggedServiceIds
     * @return array<string, array<int, array<string, mixed>>>
     */
    protected function getTaggedServiceIds(ContainerBuilder $container, string $tag): array
    {
        // 使用迭代方式替代findTaggedServiceIds，符合静态分析规则
        $taggedServices = [];

        foreach ($container->getDefinitions() as $serviceId => $definition) {
            $serviceTags = $definition->getTags();
            if (isset($serviceTags[$tag])) {
                /** @var array<int, array<string, mixed>> $tagValues */
                $tagValues = $serviceTags[$tag];
                $taggedServices[$serviceId] = $tagValues;
            }
        }

        return $taggedServices;
    }

    /**
     * 确保连接服务存在
     * @param array<string, mixed> $attributes
     */
    protected function ensureConnectionService(ContainerBuilder $container, string $channel, array $attributes = []): void
    {
        $connectionId = sprintf('redis.%s_connection', $channel);

        // 如果连接已存在，直接返回
        if ($container->hasDefinition($connectionId) || $container->hasAlias($connectionId)) {
            return;
        }

        $factory = new Reference('redis_dedicated_connection.factory');

        // 创建连接定义
        $connectionDef = new Definition(\Redis::class);
        $connectionDef->setFactory([$factory, 'createConnection']);
        $connectionDef->setArguments([$channel]);
        $connectionDef->setPublic(false);
        $connectionDef->addTag('redis.connection');

        $container->setDefinition($connectionId, $connectionDef);
    }
}
