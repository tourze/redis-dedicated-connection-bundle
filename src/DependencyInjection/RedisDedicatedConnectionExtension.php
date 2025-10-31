<?php

namespace Tourze\RedisDedicatedConnectionBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\ChildDefinition;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Tourze\RedisDedicatedConnectionBundle\Attribute\WithDedicatedConnection;
use Tourze\SymfonyDependencyServiceLoader\AutoExtension;

class RedisDedicatedConnectionExtension extends AutoExtension
{
    protected function getConfigDir(): string
    {
        return __DIR__ . '/../Resources/config';
    }

    public function load(array $configs, ContainerBuilder $container): void
    {
        parent::load($configs, $container);

        // Register attribute autoconfiguration
        $this->registerAttributeAutoconfiguration($container);
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

    public function getAlias(): string
    {
        return 'redis_dedicated_connection';
    }
}
