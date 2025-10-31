<?php

declare(strict_types=1);

namespace Tourze\RedisDedicatedConnectionBundle\Tests\Factory;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Tourze\RedisDedicatedConnectionBundle\Factory\RedisConnector;

/**
 * @internal
 */
#[CoversClass(RedisConnector::class)]
final class RedisConnectorTest extends TestCase
{
    private RedisConnector $connector;

    private LoggerInterface $logger;

    protected function setUp(): void
    {
        parent::setUp();

        $this->logger = $this->createMock(LoggerInterface::class);
        $this->connector = new RedisConnector($this->logger);
    }

    public function testConnectFailsWithoutRedisServer(): void
    {
        // 使用 Redis 具体类的 Mock 是必要的，因为：
        // 1. Redis 扩展没有提供官方的接口，只能使用具体类
        // 2. 测试连接器行为需要验证具体的 Redis 方法调用
        // 3. 这是测试 Redis 连接器的唯一可行方式
        $redis = $this->createMock(\Redis::class);
        $redis->method('connect')->willReturn(false);
        $redis->method('pconnect')->willReturn(false);

        $params = [
            'host' => 'nonexistent.example.com',
            'port' => 6379,
            'database' => 0,
            'timeout' => 1.0,
            'read_write_timeout' => 0.0,
            'persistent' => false,
            'auth' => null,
            'prefix' => null,
            'ssl' => false,
        ];

        $result = $this->connector->connect($redis, $params);

        $this->assertFalse($result);
    }

    public function testConnectWithSslHost(): void
    {
        // 使用 Redis 具体类的 Mock 是必要的，因为：
        // 1. Redis 扩展没有提供官方的接口，只能使用具体类
        // 2. 测试连接器行为需要验证具体的 Redis 方法调用
        // 3. 这是测试 Redis 连接器的唯一可行方式
        $redis = $this->createMock(\Redis::class);
        $redis->expects($this->once())
            ->method('connect')
            ->with('tls://example.com', 6379)
            ->willReturn(true)
        ;

        $redis->method('setOption')->willReturn(true);

        $params = [
            'host' => 'example.com',
            'port' => 6379,
            'database' => 0,
            'timeout' => 5.0,
            'read_write_timeout' => 0.0,
            'persistent' => false,
            'auth' => null,
            'prefix' => null,
            'ssl' => true,
        ];

        $result = $this->connector->connect($redis, $params);

        $this->assertTrue($result);
    }

    public function testConnectWithPersistent(): void
    {
        // 使用 Redis 具体类的 Mock 是必要的，因为：
        // 1. Redis 扩展没有提供官方的接口，只能使用具体类
        // 2. 测试连接器行为需要验证具体的 Redis 方法调用
        // 3. 这是测试 Redis 连接器的唯一可行方式
        $redis = $this->createMock(\Redis::class);
        $redis->expects($this->once())
            ->method('pconnect')
            ->willReturn(true)
        ;

        $redis->method('setOption')->willReturn(true);

        $params = [
            'host' => 'example.com',
            'port' => 6379,
            'database' => 0,
            'timeout' => 5.0,
            'read_write_timeout' => 0.0,
            'persistent' => true,
            'auth' => null,
            'prefix' => null,
            'ssl' => false,
        ];

        $result = $this->connector->connect($redis, $params);

        $this->assertTrue($result);
    }

    public function testConnectWithAuth(): void
    {
        // 使用 Redis 具体类的 Mock 是必要的，因为：
        // 1. Redis 扩展没有提供官方的接口，只能使用具体类
        // 2. 测试连接器行为需要验证具体的 Redis 方法调用
        // 3. 这是测试 Redis 连接器的唯一可行方式
        $redis = $this->createMock(\Redis::class);
        $redis->method('connect')->willReturn(true);
        $redis->expects($this->once())
            ->method('auth')
            ->with('password')
            ->willReturn(true)
        ;

        $redis->method('setOption')->willReturn(true);

        $params = [
            'host' => 'example.com',
            'port' => 6379,
            'database' => 0,
            'timeout' => 5.0,
            'read_write_timeout' => 0.0,
            'persistent' => false,
            'auth' => 'password',
            'prefix' => null,
            'ssl' => false,
        ];

        $result = $this->connector->connect($redis, $params);

        $this->assertTrue($result);
    }

    public function testConnectWithAclAuth(): void
    {
        // 使用 Redis 具体类的 Mock 是必要的，因为：
        // 1. Redis 扩展没有提供官方的接口，只能使用具体类
        // 2. 测试连接器行为需要验证具体的 Redis 方法调用
        // 3. 这是测试 Redis 连接器的唯一可行方式
        $redis = $this->createMock(\Redis::class);
        $redis->method('connect')->willReturn(true);
        $redis->expects($this->once())
            ->method('auth')
            ->with(['username', 'password'])
            ->willReturn(true)
        ;

        $redis->method('setOption')->willReturn(true);

        $params = [
            'host' => 'example.com',
            'port' => 6379,
            'database' => 0,
            'timeout' => 5.0,
            'read_write_timeout' => 0.0,
            'persistent' => false,
            'auth' => ['username', 'password'],
            'prefix' => null,
            'ssl' => false,
        ];

        $result = $this->connector->connect($redis, $params);

        $this->assertTrue($result);
    }

    public function testConnectWithDatabase(): void
    {
        // 使用 Redis 具体类的 Mock 是必要的，因为：
        // 1. Redis 扩展没有提供官方的接口，只能使用具体类
        // 2. 测试连接器行为需要验证具体的 Redis 方法调用
        // 3. 这是测试 Redis 连接器的唯一可行方式
        $redis = $this->createMock(\Redis::class);
        $redis->method('connect')->willReturn(true);
        $redis->expects($this->once())
            ->method('select')
            ->with(5)
            ->willReturn(true)
        ;

        $redis->method('setOption')->willReturn(true);

        $params = [
            'host' => 'example.com',
            'port' => 6379,
            'database' => 5,
            'timeout' => 5.0,
            'read_write_timeout' => 0.0,
            'persistent' => false,
            'auth' => null,
            'prefix' => null,
            'ssl' => false,
        ];

        $result = $this->connector->connect($redis, $params);

        $this->assertTrue($result);
    }

    public function testConnectWithPrefix(): void
    {
        // 使用 Redis 具体类的 Mock 是必要的，因为：
        // 1. Redis 扩展没有提供官方的接口，只能使用具体类
        // 2. 测试连接器行为需要验证具体的 Redis 方法调用
        // 3. 这是测试 Redis 连接器的唯一可行方式
        $redis = $this->createMock(\Redis::class);
        $redis->method('connect')->willReturn(true);
        $redis->expects($this->exactly(2))
            ->method('setOption')
            ->willReturn(true)
        ;

        $params = [
            'host' => 'example.com',
            'port' => 6379,
            'database' => 0,
            'timeout' => 5.0,
            'read_write_timeout' => 0.0,
            'persistent' => false,
            'auth' => null,
            'prefix' => 'test:',
            'ssl' => false,
        ];

        $result = $this->connector->connect($redis, $params);

        $this->assertTrue($result);
    }
}
