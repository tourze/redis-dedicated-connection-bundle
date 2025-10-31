<?php

declare(strict_types=1);

namespace Tourze\RedisDedicatedConnectionBundle\Tests\Factory;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\PHPUnitSymfonyKernelTest\AbstractIntegrationTestCase;
use Tourze\RedisDedicatedConnectionBundle\Factory\ConnectionParamsBuilder;

/**
 * @internal
 */
#[CoversClass(ConnectionParamsBuilder::class)]
#[RunTestsInSeparateProcesses] final class ConnectionParamsBuilderTest extends AbstractIntegrationTestCase
{
    private ConnectionParamsBuilder $builder;

    protected function onSetUp(): void
    {
        // 从容器中获取服务，避免直接实例化测试目标
        $this->builder = self::getService(ConnectionParamsBuilder::class);
    }

    protected function onTearDown(): void
    {
        // 清理环境变量
        unset(
            $_ENV['REDIS_URL'],
            $_ENV['TEST_REDIS_URL'],
            $_ENV['TEST_REDIS_HOST'],
            $_ENV['TEST_REDIS_PORT'],
            $_ENV['TEST_REDIS_DB'],
            $_ENV['TEST_REDIS_TIMEOUT'],
            $_ENV['TEST_REDIS_PERSISTENT']
        );
    }

    public function testBuildEnvKey(): void
    {
        // 测试默认频道
        $this->assertEquals('DEFAULT_REDIS_URL', $this->builder->buildEnvKey('default'));

        // 测试自定义频道
        $this->assertEquals('CACHE_REDIS_URL', $this->builder->buildEnvKey('cache'));
        $this->assertEquals('SESSION_REDIS_URL', $this->builder->buildEnvKey('session'));
        $this->assertEquals('QUEUE_REDIS_URL', $this->builder->buildEnvKey('queue'));

        // 测试小写转大写
        $this->assertEquals('TEST_REDIS_URL', $this->builder->buildEnvKey('test'));
    }

    public function testBuildDefaultParams(): void
    {
        $params = $this->builder->buildParams('default');

        $this->assertEquals('127.0.0.1', $params['host']);
        $this->assertEquals(6379, $params['port']);
        $this->assertEquals(0, $params['database']);
        $this->assertEquals(5.0, $params['timeout']);
        $this->assertFalse($params['persistent']);
        $this->assertNull($params['auth']);
        $this->assertFalse($params['ssl']);
    }

    public function testBuildParamsWithChannelUrl(): void
    {
        $_ENV['TEST_REDIS_URL'] = 'redis://test.example.com:6380/1';

        $params = $this->builder->buildParams('test');

        $this->assertEquals('test.example.com', $params['host']);
        $this->assertEquals(6380, $params['port']);
        $this->assertEquals(1, $params['database']);
    }

    public function testBuildParamsWithGlobalUrlForDefault(): void
    {
        $_ENV['REDIS_URL'] = 'redis://global.example.com:6381/2';

        $params = $this->builder->buildParams('default');

        $this->assertEquals('global.example.com', $params['host']);
        $this->assertEquals(6381, $params['port']);
        $this->assertEquals(2, $params['database']);
    }

    public function testBuildParamsWithEnvironmentOverrides(): void
    {
        $_ENV['TEST_REDIS_HOST'] = 'env.example.com';
        $_ENV['TEST_REDIS_PORT'] = '6382';
        $_ENV['TEST_REDIS_DB'] = '3';
        $_ENV['TEST_REDIS_TIMEOUT'] = '10.5';
        $_ENV['TEST_REDIS_PERSISTENT'] = 'true';

        $params = $this->builder->buildParams('test');

        $this->assertEquals('env.example.com', $params['host']);
        $this->assertEquals(6382, $params['port']);
        $this->assertEquals(3, $params['database']);
        $this->assertEquals(10.5, $params['timeout']);
        $this->assertTrue($params['persistent']);
    }

    public function testEnvironmentOverridesUrl(): void
    {
        $_ENV['TEST_REDIS_URL'] = 'redis://url.example.com:6380';
        $_ENV['TEST_REDIS_HOST'] = 'env.example.com';

        $params = $this->builder->buildParams('test');

        // 环境变量应该覆盖 URL 中的值
        $this->assertEquals('env.example.com', $params['host']);
    }
}
