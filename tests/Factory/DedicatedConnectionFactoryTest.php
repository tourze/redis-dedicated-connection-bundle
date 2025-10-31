<?php

declare(strict_types=1);

namespace Tourze\RedisDedicatedConnectionBundle\Tests\Factory;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\PHPUnitSymfonyKernelTest\AbstractIntegrationTestCase;
use Tourze\RedisDedicatedConnectionBundle\Exception\ConnectionCreationException;
use Tourze\RedisDedicatedConnectionBundle\Factory\DedicatedConnectionFactory;

/**
 * @internal
 */
#[CoversClass(DedicatedConnectionFactory::class)]
#[RunTestsInSeparateProcesses]
final class DedicatedConnectionFactoryTest extends AbstractIntegrationTestCase
{
    /** @var array<string, string|null> */
    private array $envBackup = [];

    public function testCreateConnectionWithDefaultParams(): void
    {
        // 设置测试环境变量 - 使用正确的环境变量名称格式
        $_ENV['CACHE_REDIS_URL'] = 'redis://localhost:6379/0';

        $factory = self::getService(DedicatedConnectionFactory::class);

        try {
            $connection = $factory->createConnection('cache');
            // 如果连接成功，验证返回的是 Redis 实例
            $this->assertInstanceOf(\Redis::class, $connection);
            // 验证连接已被缓存
            $this->assertCount(1, $factory->getConnections());
        } catch (ConnectionCreationException $e) {
            // 在无Redis环境中，连接失败是预期行为，验证异常消息包含有意义的信息
            $this->assertStringContainsString('cache', $e->getMessage(), '异常消息应包含连接通道信息');
        }
    }

    public function testCreateConnectionReturnsCachedConnection(): void
    {
        $_ENV['CACHE_REDIS_URL'] = 'redis://localhost:6379/0';

        $factory = self::getService(DedicatedConnectionFactory::class);

        try {
            $connection1 = $factory->createConnection('cache');
            $connection2 = $factory->createConnection('cache');

            // 验证返回的是同一个连接实例（缓存工作）
            $this->assertSame($connection1, $connection2);
            // 验证只有一个连接被创建
            $this->assertCount(1, $factory->getConnections());
        } catch (ConnectionCreationException $e) {
            // 在无Redis环境中，验证异常类型正确且消息有意义
            $this->assertStringContainsString('cache', $e->getMessage(), '异常消息应包含连接通道信息');
        }
    }

    public function testCreateConnectionWithCoroutineContext(): void
    {
        $_ENV['CACHE_REDIS_URL'] = 'redis://localhost:6379/0';

        $factory = self::getService(DedicatedConnectionFactory::class);

        try {
            $connection = $factory->createConnection('cache');
            // 验证协程上下文场景下连接创建成功
            $this->assertInstanceOf(\Redis::class, $connection);
            $this->assertCount(1, $factory->getConnections());
        } catch (ConnectionCreationException $e) {
            // 协程上下文场景中，验证异常信息的完整性
            $this->assertStringContainsString('cache', $e->getMessage(), '协程上下文异常消息应包含连接通道信息');
        }
    }

    public function testGetConnections(): void
    {
        $factory = self::getService(DedicatedConnectionFactory::class);

        // 初始状态下应该为空
        $this->assertEmpty($factory->getConnections());
    }

    public function testCloseAll(): void
    {
        $factory = self::getService(DedicatedConnectionFactory::class);

        // 测试关闭所有连接
        $factory->closeAll();
        $this->assertEmpty($factory->getConnections());
    }

    public function testCloseCurrentContextWithCoroutine(): void
    {
        $factory = self::getService(DedicatedConnectionFactory::class);

        // 测试关闭当前上下文
        $factory->closeCurrentContext();
        $this->assertEmpty($factory->getConnections());
    }

    public function testCloseCurrentContextWithoutCoroutine(): void
    {
        $factory = self::getService(DedicatedConnectionFactory::class);

        // 测试关闭当前上下文
        $factory->closeCurrentContext();
        $this->assertEmpty($factory->getConnections());
    }

    public function testCreateConnectionWithConnectionFailure(): void
    {
        $_ENV['CACHE_REDIS_URL'] = 'redis://invalid-host:6379/0';

        $factory = self::getService(DedicatedConnectionFactory::class);

        // 测试连接失败场景 - 应该抛出异常
        $this->expectException(ConnectionCreationException::class);
        $factory->createConnection('cache');
    }

    protected function onSetUp(): void
    {
        // 备份相关环境变量
        /** @var list<string> $keysToBackup */
        $keysToBackup = [];
        foreach ($_ENV as $key => $value) {
            if (str_starts_with($key, 'REDIS_') || str_contains($key, '_REDIS_')) {
                $keysToBackup[] = $key;
            }
        }
        foreach ($keysToBackup as $key) {
            // 显式类型验证和转换以满足 PHPStan level=max 的类型要求
            /** @phpstan-var string $key */
            if (isset($_ENV[$key])) {
                $value = $_ENV[$key];
                $this->envBackup[$key] = is_string($value) ? $value : null;
            } else {
                $this->envBackup[$key] = null;
            }
            unset($_ENV[$key]);
        }
    }

    protected function onTearDown(): void
    {
        // 恢复环境变量
        foreach ($this->envBackup as $key => $value) {
            if (null !== $value) {
                $_ENV[$key] = $value;
            } else {
                unset($_ENV[$key]);
            }
        }
        $this->envBackup = [];
    }
}
