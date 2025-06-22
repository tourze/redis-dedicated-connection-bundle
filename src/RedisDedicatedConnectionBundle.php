<?php

namespace Tourze\RedisDedicatedConnectionBundle;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;
use Tourze\BundleDependency\BundleDependencyInterface;
use Tourze\RedisDedicatedConnectionBundle\DependencyInjection\Compiler\ConnectionChannelPass;
use Tourze\RedisDedicatedConnectionBundle\DependencyInjection\Compiler\DedicatedConnectionCompilerPass;
use Tourze\Symfony\RuntimeContextBundle\RuntimeContextBundle;

class RedisDedicatedConnectionBundle extends Bundle implements BundleDependencyInterface
{
    public static function getBundleDependencies(): array
    {
        return [
            RuntimeContextBundle::class => ['all' => true],
        ];
    }

    public function build(ContainerBuilder $container): void
    {
        parent::build($container);

        // 注册编译器传递
        $container->addCompilerPass(new DedicatedConnectionCompilerPass());
        $container->addCompilerPass(new ConnectionChannelPass());
    }
}
