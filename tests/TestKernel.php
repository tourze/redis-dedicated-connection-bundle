<?php

namespace Tourze\RedisDedicatedConnectionBundle\Tests;

use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Kernel;
use Tourze\RedisDedicatedConnectionBundle\RedisDedicatedConnectionBundle;
use Tourze\Symfony\RuntimeContextBundle\RuntimeContextBundle;

class TestKernel extends Kernel
{
    use MicroKernelTrait;

    public function registerBundles(): iterable
    {
        return [
            new FrameworkBundle(),
            new RuntimeContextBundle(),
            new RedisDedicatedConnectionBundle(),
        ];
    }

    public function getCacheDir(): string
    {
        return sys_get_temp_dir() . '/redis_dedicated_connection_bundle/cache/' . spl_object_hash($this);
    }

    public function getLogDir(): string
    {
        return sys_get_temp_dir() . '/redis_dedicated_connection_bundle/logs';
    }

    protected function configureContainer(ContainerBuilder $container, LoaderInterface $loader): void
    {
        $container->loadFromExtension('framework', [
            'test' => true,
            'router' => [
                'resource' => __DIR__ . '/Resources/config/routes.yaml',
            ],
        ]);

        // Register test services
        $loader->load(__DIR__ . '/Resources/config/services.yaml');
    }
}