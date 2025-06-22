<?php

namespace Tourze\RedisDedicatedConnectionBundle\Tests\Integration;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Tourze\RedisDedicatedConnectionBundle\Factory\DedicatedConnectionFactory;
use Tourze\RedisDedicatedConnectionBundle\Tests\Fixtures\TestService;
use Tourze\RedisDedicatedConnectionBundle\Tests\Fixtures\TestServiceWithTag;

class BundleIntegrationTest extends KernelTestCase
{
    protected static function getKernelClass(): string
    {
        return \Tourze\RedisDedicatedConnectionBundle\Tests\TestKernel::class;
    }

    public function testBundleBoots(): void
    {
        $kernel = self::bootKernel();
        $container = $kernel->getContainer();
        
        $this->assertTrue($container->has('redis_dedicated_connection.factory'));
        $this->assertTrue($container->has(DedicatedConnectionFactory::class));
    }

    public function testFactoryService(): void
    {
        $kernel = self::bootKernel();
        $container = $kernel->getContainer();
        
        $factory = $container->get('redis_dedicated_connection.factory');
        
        $this->assertInstanceOf(DedicatedConnectionFactory::class, $factory);
    }

    public function testServiceWithAttribute(): void
    {
        if (!extension_loaded('redis')) {
            $this->markTestSkipped('Redis extension is not installed');
        }
        
        $kernel = self::bootKernel();
        $container = $kernel->getContainer();
        
        try {
            $service = $container->get(TestService::class);
            
            $this->assertInstanceOf(TestService::class, $service);
            $this->assertInstanceOf(\Redis::class, $service->getRedis());
        } catch (\Exception $e) {
            if (strpos($e->getMessage(), 'Redis') !== false) {
                $this->markTestSkipped('Redis server is not available');
            }
            throw $e;
        }
    }

    public function testServiceWithTag(): void
    {
        if (!extension_loaded('redis')) {
            $this->markTestSkipped('Redis extension is not installed');
        }
        
        $kernel = self::bootKernel();
        $container = $kernel->getContainer();
        
        try {
            $service = $container->get('test.service_with_tag');
            
            $this->assertInstanceOf(TestServiceWithTag::class, $service);
            $this->assertInstanceOf(\Redis::class, $service->getRedis());
        } catch (\Exception $e) {
            if (strpos($e->getMessage(), 'Redis') !== false) {
                $this->markTestSkipped('Redis server is not available');
            }
            throw $e;
        }
    }

    public function testMultipleServicesGetDifferentConnections(): void
    {
        if (!extension_loaded('redis')) {
            $this->markTestSkipped('Redis extension is not installed');
        }
        
        $kernel = self::bootKernel();
        $container = $kernel->getContainer();
        
        try {
            // Test service uses 'test' channel
            $service1 = $container->get(TestService::class);
            
            // Tagged service uses 'tagged' channel
            $service2 = $container->get('test.service_with_tag');
            
            $this->assertNotSame($service1->getRedis(), $service2->getRedis());
        } catch (\Exception $e) {
            if (strpos($e->getMessage(), 'Redis') !== false) {
                $this->markTestSkipped('Redis server is not available');
            }
            throw $e;
        }
    }
}