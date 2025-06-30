<?php

declare(strict_types=1);

namespace Tourze\RedisDedicatedConnectionBundle\Tests\DependencyInjection;

use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Tourze\RedisDedicatedConnectionBundle\DependencyInjection\RedisDedicatedConnectionExtension;

class RedisDedicatedConnectionExtensionTest extends TestCase
{
    private RedisDedicatedConnectionExtension $extension;
    private ContainerBuilder $container;

    protected function setUp(): void
    {
        $this->extension = new RedisDedicatedConnectionExtension();
        $this->container = new ContainerBuilder();
    }

    public function testLoadWithDefaultConfig(): void
    {
        $this->extension->load([], $this->container);

        // 验证服务是否被加载
        $this->assertTrue($this->container->hasDefinition('redis_dedicated_connection.factory'));
        
        // 验证别名是否被创建
        $this->assertTrue($this->container->hasAlias('Tourze\RedisDedicatedConnectionBundle\Factory\DedicatedConnectionFactory'));
    }

    public function testGetAlias(): void
    {
        $this->assertSame('redis_dedicated_connection', $this->extension->getAlias());
    }
}