<?php

declare(strict_types=1);

namespace Tourze\RedisDedicatedConnectionBundle\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Tourze\BundleDependency\BundleDependencyInterface;
use Tourze\RedisDedicatedConnectionBundle\DependencyInjection\Compiler\ConnectionChannelPass;
use Tourze\RedisDedicatedConnectionBundle\DependencyInjection\Compiler\DedicatedConnectionCompilerPass;
use Tourze\RedisDedicatedConnectionBundle\RedisDedicatedConnectionBundle;
use Tourze\Symfony\RuntimeContextBundle\RuntimeContextBundle;

class RedisDedicatedConnectionBundleTest extends TestCase
{
    private RedisDedicatedConnectionBundle $bundle;

    protected function setUp(): void
    {
        $this->bundle = new RedisDedicatedConnectionBundle();
    }

    public function testBundleImplementsBundleDependencyInterface(): void
    {
        $this->assertInstanceOf(BundleDependencyInterface::class, $this->bundle);
    }

    public function testGetBundleDependencies(): void
    {
        $dependencies = RedisDedicatedConnectionBundle::getBundleDependencies();
        
        $this->assertArrayHasKey(RuntimeContextBundle::class, $dependencies);
        $this->assertEquals(['all' => true], $dependencies[RuntimeContextBundle::class]);
    }

    public function testBuildRegistersCompilerPasses(): void
    {
        $container = new ContainerBuilder();
        
        // 记录当前的 compiler passes 数量
        $beforeCount = count($container->getCompilerPassConfig()->getPasses());
        
        $this->bundle->build($container);
        
        // 检查是否增加了 2 个 compiler passes
        $afterCount = count($container->getCompilerPassConfig()->getPasses());
        $this->assertEquals($beforeCount + 2, $afterCount);
        
        // 检查是否包含正确的 compiler passes
        $passes = $container->getCompilerPassConfig()->getPasses();
        $passClasses = array_map(function ($pass) {
            return get_class($pass);
        }, $passes);
        
        $this->assertContains(DedicatedConnectionCompilerPass::class, $passClasses);
        $this->assertContains(ConnectionChannelPass::class, $passClasses);
    }

    public function testBuildRegistersCompilerPassesInOrder(): void
    {
        $container = new ContainerBuilder();
        
        // 记录添加前的 passes
        $beforePasses = $container->getCompilerPassConfig()->getPasses();
        
        $this->bundle->build($container);
        
        // 获取所有 passes
        $allPasses = $container->getCompilerPassConfig()->getPasses();
        
        // 找出新添加的与本 bundle 相关的 passes
        $bundlePasses = [];
        foreach ($allPasses as $pass) {
            if ($pass instanceof DedicatedConnectionCompilerPass || $pass instanceof ConnectionChannelPass) {
                $bundlePasses[] = $pass;
            }
        }
        
        // 确保两个 pass 都被添加了
        $this->assertCount(2, $bundlePasses);
        
        // 确保 DedicatedConnectionCompilerPass 在 ConnectionChannelPass 之前
        $dedicatedIndex = -1;
        $channelIndex = -1;
        
        foreach ($bundlePasses as $index => $pass) {
            if ($pass instanceof DedicatedConnectionCompilerPass) {
                $dedicatedIndex = $index;
            } elseif ($pass instanceof ConnectionChannelPass) {
                $channelIndex = $index;
            }
        }
        
        $this->assertGreaterThan(-1, $dedicatedIndex, 'DedicatedConnectionCompilerPass was not found');
        $this->assertGreaterThan(-1, $channelIndex, 'ConnectionChannelPass was not found');
        $this->assertLessThan($channelIndex, $dedicatedIndex, 'DedicatedConnectionCompilerPass should be registered before ConnectionChannelPass');
    }
}