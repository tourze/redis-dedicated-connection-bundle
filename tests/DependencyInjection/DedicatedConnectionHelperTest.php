<?php

declare(strict_types=1);

namespace Tourze\RedisDedicatedConnectionBundle\Tests\DependencyInjection;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;
use Tourze\RedisDedicatedConnectionBundle\DependencyInjection\DedicatedConnectionHelper;
use Tourze\RedisDedicatedConnectionBundle\Exception\InvalidChannelException;

/**
 * @internal
 */
#[CoversClass(DedicatedConnectionHelper::class)]
final class DedicatedConnectionHelperTest extends TestCase
{
    private ContainerBuilder $container;

    public function testAddDedicatedConnectionWithDefinition(): void
    {
        $definition = new Definition('TestService');

        $result = DedicatedConnectionHelper::addDedicatedConnection($definition, 'cache');

        $this->assertSame($definition, $result);
        $this->assertTrue($definition->hasTag('redis.dedicated_connection'));

        $tags = $definition->getTag('redis.dedicated_connection');
        $this->assertCount(1, $tags);
        $this->assertIsArray($tags[0] ?? null);
        /** @var array{channel: string} $firstTag */
        $firstTag = $tags[0];
        $this->assertSame('cache', $firstTag['channel']);
    }

    public function testAddDedicatedConnectionWithServiceId(): void
    {
        $definition = new Definition('TestService');
        $this->container->setDefinition('test.service', $definition);

        $result = DedicatedConnectionHelper::addDedicatedConnection('test.service', 'cache', $this->container);

        $this->assertSame($definition, $result);
        $this->assertTrue($definition->hasTag('redis.dedicated_connection'));
    }

    public function testAddDedicatedConnectionWithServiceIdWithoutContainer(): void
    {
        $this->expectException(InvalidChannelException::class);
        $this->expectExceptionMessage('Container must be provided when using service ID');

        DedicatedConnectionHelper::addDedicatedConnection('test.service', 'cache');
    }

    public function testAddConnectionChannel(): void
    {
        $definition = new Definition('TestService');

        $result = DedicatedConnectionHelper::addConnectionChannel($definition, 'cache');

        $this->assertSame($definition, $result);
        $this->assertTrue($definition->hasTag('redis.connection_channel'));

        $tags = $definition->getTag('redis.connection_channel');
        $this->assertCount(1, $tags);
        $this->assertIsArray($tags[0] ?? null);
        /** @var array{channel: string} $firstTag */
        $firstTag = $tags[0];
        $this->assertSame('cache', $firstTag['channel']);
    }

    public function testCreateConnectionReference(): void
    {
        $reference = DedicatedConnectionHelper::createConnectionReference('cache');

        $this->assertInstanceOf(Reference::class, $reference);
        $this->assertSame('redis.cache_connection', (string) $reference);
    }

    public function testHasConnection(): void
    {
        $this->assertFalse(DedicatedConnectionHelper::hasConnection($this->container, 'cache'));

        $this->container->setDefinition('redis.cache_connection', new Definition());

        $this->assertTrue(DedicatedConnectionHelper::hasConnection($this->container, 'cache'));
    }

    public function testGetConnectionId(): void
    {
        $this->assertSame('redis.cache_connection', DedicatedConnectionHelper::getConnectionId('cache'));
        $this->assertSame('redis.session_connection', DedicatedConnectionHelper::getConnectionId('session'));
    }

    public function testAddMultipleDedicatedConnections(): void
    {
        $definition = new Definition('TestService');
        $channels = ['cache', 'session', 'queue'];

        $result = DedicatedConnectionHelper::addMultipleDedicatedConnections($definition, $channels);

        $this->assertSame($definition, $result);
        $this->assertTrue($definition->hasTag('redis.dedicated_connection'));

        $tags = $definition->getTag('redis.dedicated_connection');
        $this->assertCount(3, $tags);

        $taggedChannels = array_column($tags, 'channel');
        $this->assertSame($channels, $taggedChannels);
    }

    public function testCreateServiceWithConnection(): void
    {
        $definition = DedicatedConnectionHelper::createServiceWithConnection(
            'TestService',
            'cache',
            ['@logger', '%kernel.debug%']
        );

        $this->assertInstanceOf(Definition::class, $definition);
        $this->assertSame('TestService', $definition->getClass());

        $arguments = $definition->getArguments();
        $this->assertCount(3, $arguments);
        $this->assertInstanceOf(Reference::class, $arguments[0]);
        $this->assertSame('redis.cache_connection', (string) $arguments[0]);
        $this->assertSame('@logger', $arguments[1]);
        $this->assertSame('%kernel.debug%', $arguments[2]);

        $this->assertTrue($definition->hasTag('redis.dedicated_connection'));
        $tags = $definition->getTag('redis.dedicated_connection');
        $this->assertIsArray($tags[0] ?? null);
        /** @var array{channel: string} $firstTag */
        $firstTag = $tags[0];
        $this->assertSame('cache', $firstTag['channel']);
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->container = new ContainerBuilder();
    }
}
