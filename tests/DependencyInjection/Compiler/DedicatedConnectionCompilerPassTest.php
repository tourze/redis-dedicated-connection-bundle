<?php

declare(strict_types=1);

namespace Tourze\RedisDedicatedConnectionBundle\Tests\DependencyInjection\Compiler;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\Argument\BoundArgument;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;
use Tourze\RedisDedicatedConnectionBundle\DependencyInjection\Compiler\DedicatedConnectionCompilerPass;
use Tourze\RedisDedicatedConnectionBundle\Exception\InvalidChannelException;

/**
 * @internal
 */
#[CoversClass(DedicatedConnectionCompilerPass::class)]
final class DedicatedConnectionCompilerPassTest extends TestCase
{
    private ContainerBuilder $container;

    private DedicatedConnectionCompilerPass $pass;

    protected function setUp(): void
    {
        parent::setUp();
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

        // process 方法在没有 factory 时应该正常执行不抛出异常
        $pass->process($container);

        // 验证容器中没有 factory 定义时的状态
        $this->assertFalse($container->hasDefinition('redis_dedicated_connection.factory'));
    }

    public function testProcessWithValidTag(): void
    {
        $serviceDef = new Definition();
        $serviceDef->addArgument(new Reference('some.service'));
        $serviceDef->addTag('redis.dedicated_connection', ['channel' => 'cache']);

        $this->container->setDefinition('test.service', $serviceDef);

        $this->pass->process($this->container);

        // 验证使用参数绑定设置了 Redis 连接
        $bindings = $this->container->getDefinition('test.service')->getBindings();
        $this->assertArrayHasKey('$redis', $bindings);
        $this->assertInstanceOf(BoundArgument::class, $bindings['$redis']);
        $boundValue = $bindings['$redis']->getValues()[0];
        $this->assertInstanceOf(Reference::class, $boundValue);
        $this->assertSame('redis.cache_connection', (string) $boundValue);

        // 验证创建了连接定义
        $this->assertTrue($this->container->hasDefinition('redis.cache_connection'));
    }

    public function testProcessWithExistingRedisArgument(): void
    {
        $serviceDef = new Definition();
        $serviceDef->addArgument(new Reference('redis.default'));
        $serviceDef->addTag('redis.dedicated_connection', ['channel' => 'cache']);

        $this->container->setDefinition('test.service', $serviceDef);

        $this->pass->process($this->container);

        // 验证使用参数绑定设置了专用 Redis 连接（原始参数保持不变）
        $bindings = $this->container->getDefinition('test.service')->getBindings();
        $this->assertArrayHasKey('$redis', $bindings);
        $this->assertInstanceOf(BoundArgument::class, $bindings['$redis']);
        $boundValue = $bindings['$redis']->getValues()[0];
        $this->assertInstanceOf(Reference::class, $boundValue);
        $this->assertSame('redis.cache_connection', (string) $boundValue);

        // 验证原始参数仍然存在
        $arguments = $this->container->getDefinition('test.service')->getArguments();
        $this->assertCount(1, $arguments);
        $this->assertInstanceOf(Reference::class, $arguments[0]);
        $this->assertSame('redis.default', (string) $arguments[0]);
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
