<?php

namespace Tourze\RedisDedicatedConnectionBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

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
        $taggedServices = $container->findTaggedServiceIds('redis.dedicated_connection');
        
        foreach ($taggedServices as $id => $tags) {
            $definition = $container->getDefinition($id);

            foreach ($tags as $attributes) {
                $channel = $attributes['channel'] ?? null;
                if (!$channel) {
                    throw new \InvalidArgumentException(sprintf(
                        'The "redis.dedicated_connection" tag on service "%s" must have a "channel" attribute.',
                        $id
                    ));
                }

                $this->ensureConnectionService($container, $channel, $attributes);
                $this->bindConnectionToService($container, $definition, $channel);
            }
        }
    }

    /**
     * 绑定连接到服务
     */
    private function bindConnectionToService(ContainerBuilder $container, Definition $definition, string $channel): void
    {
        $connectionId = sprintf('redis.%s_connection', $channel);
        
        // 尝试自动绑定到构造函数参数
        $this->autoBindToConstructor($definition, $connectionId);
    }

    /**
     * 自动绑定到构造函数
     */
    private function autoBindToConstructor(Definition $definition, string $connectionId): void
    {
        $arguments = $definition->getArguments();
        
        // 查找合适的参数位置
        foreach ($arguments as $index => $argument) {
            if ($argument instanceof Reference) {
                $refId = (string) $argument;
                // 如果参数是 redis 相关的服务，替换它
                if (str_contains($refId, 'redis')) {
                    $arguments[$index] = new Reference($connectionId);
                    $definition->setArguments($arguments);
                    return;
                }
            }
        }

        // 如果没有找到合适的参数，添加到构造函数末尾
        $definition->addArgument(new Reference($connectionId));
    }
}