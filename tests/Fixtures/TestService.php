<?php

namespace Tourze\RedisDedicatedConnectionBundle\Tests\Fixtures;

use Tourze\RedisDedicatedConnectionBundle\Attribute\WithDedicatedConnection;

#[WithDedicatedConnection(channel: 'test')]
class TestService
{
    public function __construct(
        private readonly \Redis $redis,
    ) {
    }

    public function getRedis(): \Redis
    {
        return $this->redis;
    }
}
