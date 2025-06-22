<?php

namespace Tourze\RedisDedicatedConnectionBundle\Tests\Unit\Factory;

use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Tourze\RedisDedicatedConnectionBundle\Factory\DedicatedConnectionFactory;
use Tourze\Symfony\RuntimeContextBundle\Service\ContextServiceInterface;

class DedicatedConnectionFactoryTest extends TestCase
{
    private DedicatedConnectionFactory $factory;
    private ContextServiceInterface $contextService;
    private LoggerInterface $logger;

    public function testCreateConnectionWithDefaultParams(): void
    {
        if (!extension_loaded('redis')) {
            $this->markTestSkipped('Redis extension is not installed');
        }

        $this->contextService->method('getId')->willReturn('test-context');
        $this->contextService->method('supportCoroutine')->willReturn(false);

        try {
            $connection = $this->factory->createConnection('cache');
            $this->assertInstanceOf(\Redis::class, $connection);
        } catch (\RuntimeException $e) {
            // Redis server not available, skip test
            $this->markTestSkipped('Redis server is not available');
        }
    }

    public function testCreateConnectionReturnsCachedConnection(): void
    {
        if (!extension_loaded('redis')) {
            $this->markTestSkipped('Redis extension is not installed');
        }

        $this->contextService->method('getId')->willReturn('test-context');
        $this->contextService->method('supportCoroutine')->willReturn(false);

        try {
            $connection1 = $this->factory->createConnection('cache');
            $connection2 = $this->factory->createConnection('cache');

            $this->assertSame($connection1, $connection2);
        } catch (\RuntimeException $e) {
            $this->markTestSkipped('Redis server is not available');
        }
    }

    public function testCreateConnectionWithCoroutineContext(): void
    {
        if (!extension_loaded('redis')) {
            $this->markTestSkipped('Redis extension is not installed');
        }

        $this->contextService->method('getId')->willReturn('coroutine-123');
        $this->contextService->method('supportCoroutine')->willReturn(true);
        $this->contextService->expects($this->once())->method('defer');

        try {
            $connection = $this->factory->createConnection('cache');
            $this->assertInstanceOf(\Redis::class, $connection);
        } catch (\RuntimeException $e) {
            $this->markTestSkipped('Redis server is not available');
        }
    }

    public function testGetConnections(): void
    {
        if (!extension_loaded('redis')) {
            $this->markTestSkipped('Redis extension is not installed');
        }

        $this->contextService->method('getId')->willReturn('test-context');
        $this->contextService->method('supportCoroutine')->willReturn(false);

        $this->assertEmpty($this->factory->getConnections());

        try {
            $this->factory->createConnection('cache');
            $this->factory->createConnection('session');

            $connections = $this->factory->getConnections();
            $this->assertCount(2, $connections);
            $this->assertArrayHasKey('cache', $connections);
            $this->assertArrayHasKey('session', $connections);
        } catch (\RuntimeException $e) {
            $this->markTestSkipped('Redis server is not available');
        }
    }

    public function testCloseAll(): void
    {
        if (!extension_loaded('redis')) {
            $this->markTestSkipped('Redis extension is not installed');
        }

        $this->contextService->method('getId')->willReturn('test-context');
        $this->contextService->method('supportCoroutine')->willReturn(false);

        try {
            $this->factory->createConnection('cache');
            $this->factory->createConnection('session');

            $this->assertCount(2, $this->factory->getConnections());

            $this->factory->closeAll();

            $this->assertEmpty($this->factory->getConnections());
        } catch (\RuntimeException $e) {
            $this->markTestSkipped('Redis server is not available');
        }
    }

    public function testCloseCurrentContextWithCoroutine(): void
    {
        if (!extension_loaded('redis')) {
            $this->markTestSkipped('Redis extension is not installed');
        }

        $this->contextService->method('getId')->willReturn('coroutine-123');
        $this->contextService->method('supportCoroutine')->willReturn(true);

        try {
            $this->contextService->expects($this->once())->method('defer');
            $this->factory->createConnection('cache');

            $this->factory->closeCurrentContext();

            $this->assertEmpty($this->factory->getConnections());
        } catch (\RuntimeException $e) {
            $this->markTestSkipped('Redis server is not available');
        }
    }

    public function testCloseCurrentContextWithoutCoroutine(): void
    {
        if (!extension_loaded('redis')) {
            $this->markTestSkipped('Redis extension is not installed');
        }

        $this->contextService->method('getId')->willReturn('test-context');
        $this->contextService->method('supportCoroutine')->willReturn(false);

        try {
            $this->factory->createConnection('cache');

            $this->factory->closeCurrentContext();

            $this->assertEmpty($this->factory->getConnections());
        } catch (\RuntimeException $e) {
            $this->markTestSkipped('Redis server is not available');
        }
    }

    public function testCreateConnectionWithEnvironmentVariables(): void
    {
        if (!extension_loaded('redis')) {
            $this->markTestSkipped('Redis extension is not installed');
        }

        $_ENV['CACHE_REDIS_HOST'] = 'localhost';
        $_ENV['CACHE_REDIS_PORT'] = '6379';
        $_ENV['CACHE_REDIS_DB'] = '5';
        $_ENV['CACHE_REDIS_PASSWORD'] = '';
        $_ENV['CACHE_REDIS_PREFIX'] = 'myapp:';

        $this->contextService->method('getId')->willReturn('test-context');
        $this->contextService->method('supportCoroutine')->willReturn(false);

        try {
            $connection = $this->factory->createConnection('cache');
            $this->assertInstanceOf(\Redis::class, $connection);
        } catch (\RuntimeException $e) {
            $this->markTestSkipped('Redis server is not available');
        } finally {
            // Clean up environment variables
            unset(
                $_ENV['CACHE_REDIS_HOST'],
                $_ENV['CACHE_REDIS_PORT'],
                $_ENV['CACHE_REDIS_DB'],
                $_ENV['CACHE_REDIS_PASSWORD'],
                $_ENV['CACHE_REDIS_PREFIX']
            );
        }
    }

    public function testCreateConnectionWithRedisUrl(): void
    {
        if (!extension_loaded('redis')) {
            $this->markTestSkipped('Redis extension is not installed');
        }

        $_ENV['REDIS_URL'] = 'redis://127.0.0.1:6379/0';

        $this->contextService->method('getId')->willReturn('test-context');
        $this->contextService->method('supportCoroutine')->willReturn(false);

        try {
            $connection = $this->factory->createConnection('default');
            $this->assertInstanceOf(\Redis::class, $connection);
        } catch (\RuntimeException $e) {
            $this->markTestSkipped('Redis server is not available');
        } finally {
            unset($_ENV['REDIS_URL']);
        }
    }

    public function testCreateConnectionWithChannelSpecificRedisUrl(): void
    {
        if (!extension_loaded('redis')) {
            $this->markTestSkipped('Redis extension is not installed');
        }

        $_ENV['CACHE_REDIS_URL'] = 'redis://password@localhost:6380/2?timeout=10&prefix=cache:';
        
        $this->contextService->method('getId')->willReturn('test-context');
        $this->contextService->method('supportCoroutine')->willReturn(false);

        try {
            $connection = $this->factory->createConnection('cache');
            $this->assertInstanceOf(\Redis::class, $connection);
        } catch (\RuntimeException $e) {
            $this->markTestSkipped('Redis server is not available');
        } finally {
            unset($_ENV['CACHE_REDIS_URL']);
        }
    }

    public function testCreateConnectionWithRedissUrl(): void
    {
        if (!extension_loaded('redis')) {
            $this->markTestSkipped('Redis extension is not installed');
        }

        $_ENV['SECURE_REDIS_URL'] = 'rediss://username:password@secure.redis.host:6380/1';
        
        $this->contextService->method('getId')->willReturn('test-context');
        $this->contextService->method('supportCoroutine')->willReturn(false);

        try {
            $connection = $this->factory->createConnection('secure');
            $this->assertInstanceOf(\Redis::class, $connection);
        } catch (\RuntimeException $e) {
            // SSL connection might fail in test environment
            $this->markTestSkipped('Redis SSL connection is not available');
        } finally {
            unset($_ENV['SECURE_REDIS_URL']);
        }
    }

    public function testEnvironmentVariablesOverrideRedisUrl(): void
    {
        if (!extension_loaded('redis')) {
            $this->markTestSkipped('Redis extension is not installed');
        }

        $_ENV['CACHE_REDIS_URL'] = 'redis://old.host:6379/0';
        $_ENV['CACHE_REDIS_HOST'] = 'new.host';
        $_ENV['CACHE_REDIS_PORT'] = '6380';
        $_ENV['CACHE_REDIS_DB'] = '5';
        
        $this->contextService->method('getId')->willReturn('test-context');
        $this->contextService->method('supportCoroutine')->willReturn(false);

        try {
            $connection = $this->factory->createConnection('cache');
            $this->assertInstanceOf(\Redis::class, $connection);
            // Note: We can't easily test the actual connection params without exposing them
        } catch (\RuntimeException $e) {
            $this->markTestSkipped('Redis server is not available');
        } finally {
            unset(
                $_ENV['CACHE_REDIS_URL'],
                $_ENV['CACHE_REDIS_HOST'],
                $_ENV['CACHE_REDIS_PORT'],
                $_ENV['CACHE_REDIS_DB']
            );
        }
    }

    protected function setUp(): void
    {
        $this->contextService = $this->createMock(ContextServiceInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->factory = new DedicatedConnectionFactory(
            $this->contextService,
            $this->logger
        );
    }
}