<?php

declare(strict_types=1);

namespace Tourze\RedisDedicatedConnectionBundle\Tests\DependencyInjection;

use PHPUnit\Framework\Attributes\CoversClass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Tourze\PHPUnitSymfonyUnitTest\AbstractDependencyInjectionExtensionTestCase;
use Tourze\RedisDedicatedConnectionBundle\DependencyInjection\RedisDedicatedConnectionExtension;

/**
 * @internal
 */
#[CoversClass(RedisDedicatedConnectionExtension::class)]
final class RedisDedicatedConnectionExtensionTest extends AbstractDependencyInjectionExtensionTestCase
{
    private RedisDedicatedConnectionExtension $extension;

    private ContainerBuilder $container;

    protected function setUp(): void
    {
        parent::setUp();
        $this->extension = new RedisDedicatedConnectionExtension();
        $this->container = new ContainerBuilder();
        $this->container->setParameter('kernel.environment', 'test');
    }

    public function testLoadWithDefaultConfig(): void
    {
        $this->extension->load([], $this->container);

        // 验证服务是否被加载
        $this->assertTrue($this->container->hasDefinition('redis_dedicated_connection.factory'));
    }

    public function testGetAlias(): void
    {
        $this->assertSame('redis_dedicated_connection', $this->extension->getAlias());
    }

    protected function provideServiceDirectories(): iterable
    {
        return [];
    }
}
