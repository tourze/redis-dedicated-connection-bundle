<?php

namespace Tourze\RedisDedicatedConnectionBundle\Attribute;

/**
 * 标记一个服务需要使用专用的 Redis 连接
 *
 * 使用示例：
 * ```php
 * #[WithDedicatedConnection('cache')]
 * class 缓存服务
 * {
 *     public function __construct(
 *         private readonly \Redis $redis
 *     ) {}
 * }
 * ```
 *
 * 该注解会自动创建专用的 Redis 连接并注入到服务中
 * Redis 配置通过环境变量管理，例如：CACHE_REDIS_HOST, CACHE_REDIS_PORT 等
 */
#[\Attribute(flags: \Attribute::TARGET_CLASS)]
readonly class WithDedicatedConnection
{
    /**
     * @param string $channel 连接通道名称，用于标识不同的 Redis 连接
     */
    public function __construct(
        public string $channel,
    ) {
    }
}
