<?php

namespace Tourze\RedisDedicatedConnectionBundle\Tests\Unit\Attribute;

use PHPUnit\Framework\TestCase;
use Tourze\RedisDedicatedConnectionBundle\Attribute\WithDedicatedConnection;

class WithDedicatedConnectionTest extends TestCase
{
    public function testConstructor(): void
    {
        $attribute = new WithDedicatedConnection('cache');
        
        $this->assertSame('cache', $attribute->channel);
    }

    public function testAttributeTarget(): void
    {
        $reflection = new \ReflectionClass(WithDedicatedConnection::class);
        $attributes = $reflection->getAttributes(\Attribute::class);
        
        $this->assertCount(1, $attributes);
        
        $attributeInstance = $attributes[0]->newInstance();
        $this->assertSame(\Attribute::TARGET_CLASS, $attributeInstance->flags);
    }
}