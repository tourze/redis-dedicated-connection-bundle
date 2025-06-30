<?php

declare(strict_types=1);

namespace Tourze\RedisDedicatedConnectionBundle\Tests\DependencyInjection\Compiler;

use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;
use Tourze\RedisDedicatedConnectionBundle\DependencyInjection\Compiler\ConnectionChannelPass;
use Tourze\RedisDedicatedConnectionBundle\Exception\InvalidChannelException;

class ConnectionChannelPassTest extends TestCase
{
    private ContainerBuilder $container;
    private ConnectionChannelPass $pass;

    protected function setUp(): void
    {
        $this->container = new ContainerBuilder();
        $this->pass = new ConnectionChannelPass();
        
        // 设置 factory 定义
        $factory = new Definition();
        $this->container->setDefinition('redis_dedicated_connection.factory', $factory);
    }

    public function testProcessWithoutFactory(): void
    {
        $container = new ContainerBuilder();
        $pass = new ConnectionChannelPass();
        
        // 不应该抛出异常
        $pass->process($container);
        $this->assertTrue(true);
    }

    public function testProcessWithValidTag(): void
    {
        $serviceDef = new Definition();
        $serviceDef->addArgument(new Reference('redis'));
        $serviceDef->addTag('redis.connection_channel', ['channel' => 'cache']);
        
        $this->container->setDefinition('test.service', $serviceDef);
        
        $this->pass->process($this->container);
        
        // 验证参数被替换
        $arguments = $this->container->getDefinition('test.service')->getArguments();
        $this->assertInstanceOf(Reference::class, $arguments[0]);
        $this->assertSame('redis.cache_connection', (string)$arguments[0]);
    }

    public function testProcessWithoutChannelAttribute(): void
    {
        $serviceDef = new Definition();
        $serviceDef->addTag('redis.connection_channel');
        
        $this->container->setDefinition('test.service', $serviceDef);
        
        $this->expectException(InvalidChannelException::class);
        $this->expectExceptionMessage('The "redis.connection_channel" tag on service "test.service" must have a "channel" attribute.');
        
        $this->pass->process($this->container);
    }

    public function testProcessMethodCalls(): void
    {
        $serviceDef = new Definition();
        $serviceDef->addMethodCall('setRedis', [new Reference('redis')]);
        $serviceDef->addTag('redis.connection_channel', ['channel' => 'cache']);
        
        $this->container->setDefinition('test.service', $serviceDef);
        
        $this->pass->process($this->container);
        
        // 验证方法调用参数被替换
        $methodCalls = $this->container->getDefinition('test.service')->getMethodCalls();
        $this->assertCount(1, $methodCalls);
        $this->assertSame('setRedis', $methodCalls[0][0]);
        $this->assertInstanceOf(Reference::class, $methodCalls[0][1][0]);
        $this->assertSame('redis.cache_connection', (string)$methodCalls[0][1][0]);
    }
}