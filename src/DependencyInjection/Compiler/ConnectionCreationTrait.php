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
     * 确保连接服务存在
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