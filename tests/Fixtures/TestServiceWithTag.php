<?php

namespace Tourze\RedisDedicatedConnectionBundle\Tests\Fixtures;

class TestServiceWithTag
{
    private ?\Redis $redis = null;

    public function getRedis(): ?\Redis
    {
        return $this->redis;
    }

    public function setRedis(\Redis $redis): void
    {
        $this->redis = $redis;
    }
}
