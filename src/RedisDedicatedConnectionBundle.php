<?php

namespace Tourze\RedisDedicatedConnectionBundle;

use Symfony\Component\DependencyInjection\ChildDefinition;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;
use Tourze\BundleDependency\BundleDependencyInterface;
use Tourze\RedisDedicatedConnectionBundle\Attribute\WithDedicatedConnection;
use Tourze\RedisDedicatedConnectionBundle\DependencyInjection\Compiler\ConnectionChannelPass;
use Tourze\RedisDedicatedConnectionBundle\DependencyInjection\Compiler\DedicatedConnectionCompilerPass;
use Tourze\RedisDedicatedConnectionBundle\Factory\DedicatedConnectionFactory;
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

        // Ensure the factory is available for compiler passes
        $container->setAlias(DedicatedConnectionFactory::class, 'redis_dedicated_connection.factory')
            ->setPublic(true)
        ;

        // Register attribute autoconfiguration
        $this->registerAttributeAutoconfiguration($container);

        // 注册编译器传递
        $container->addCompilerPass(new DedicatedConnectionCompilerPass());
        $container->addCompilerPass(new ConnectionChannelPass());
    }

    private function registerAttributeAutoconfiguration(ContainerBuilder $container): void
    {
        $container->registerAttributeForAutoconfiguration(
            WithDedicatedConnection::class,
            static function (ChildDefinition $definition, WithDedicatedConnection $attribute): void {
                $definition->addTag('redis.dedicated_connection', [
                    'channel' => $attribute->channel,
                ]);
            }
        );
    }
}
