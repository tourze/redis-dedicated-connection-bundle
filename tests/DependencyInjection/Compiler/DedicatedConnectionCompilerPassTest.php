<?php

declare(strict_types=1);

namespace Tourze\RedisDedicatedConnectionBundle\Tests\DependencyInjection\Compiler;

use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;
use Tourze\RedisDedicatedConnectionBundle\DependencyInjection\Compiler\DedicatedConnectionCompilerPass;
use Tourze\RedisDedicatedConnectionBundle\Exception\InvalidChannelException;

class DedicatedConnectionCompilerPassTest extends TestCase
{
    private ContainerBuilder $container;
    private DedicatedConnectionCompilerPass $pass;

    protected function setUp(): void
    {
        $this->container = new ContainerBuilder();
        $this->pass = new DedicatedConnectionCompilerPass();
        
        // 设置 factory 定义
        $factory = new Definition();
        $this->container->setDefinition('redis_dedicated_connection.factory', $factory);
    }

    public function testProcessWithoutFactory(): void
    {
        $container = new ContainerBuilder();
        $pass = new DedicatedConnectionCompilerPass();
        
        // 不应该抛出异常
        $pass->process($container);
        $this->assertTrue(true);
    }

    public function testProcessWithValidTag(): void
    {
        $serviceDef = new Definition();
        $serviceDef->addArgument(new Reference('some.service'));
        $serviceDef->addTag('redis.dedicated_connection', ['channel' => 'cache']);
        
        $this->container->setDefinition('test.service', $serviceDef);
        
        $this->pass->process($this->container);
        
        // 验证自动添加了 Redis 连接参数
        $arguments = $this->container->getDefinition('test.service')->getArguments();
        $this->assertCount(2, $arguments);
        $this->assertInstanceOf(Reference::class, $arguments[1]);
        $this->assertSame('redis.cache_connection', (string)$arguments[1]);
    }

    public function testProcessWithExistingRedisArgument(): void
    {
        $serviceDef = new Definition();
        $serviceDef->addArgument(new Reference('redis.default'));
        $serviceDef->addTag('redis.dedicated_connection', ['channel' => 'cache']);
        
        $this->container->setDefinition('test.service', $serviceDef);
        
        $this->pass->process($this->container);
        
        // 验证 Redis 参数被替换
        $arguments = $this->container->getDefinition('test.service')->getArguments();
        $this->assertCount(1, $arguments);
        $this->assertInstanceOf(Reference::class, $arguments[0]);
        $this->assertSame('redis.cache_connection', (string)$arguments[0]);
    }

    public function testProcessWithoutChannelAttribute(): void
    {
        $serviceDef = new Definition();
        $serviceDef->addTag('redis.dedicated_connection');
        
        $this->container->setDefinition('test.service', $serviceDef);
        
        $this->expectException(InvalidChannelException::class);
        $this->expectExceptionMessage('The "redis.dedicated_connection" tag on service "test.service" must have a "channel" attribute.');
        
        $this->pass->process($this->container);
    }
}