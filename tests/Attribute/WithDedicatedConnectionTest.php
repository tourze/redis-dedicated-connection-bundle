<?php

declare(strict_types=1);

namespace Tourze\RedisDedicatedConnectionBundle\Tests\Attribute;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Tourze\RedisDedicatedConnectionBundle\Attribute\WithDedicatedConnection;

/**
 * @internal
 */
#[CoversClass(WithDedicatedConnection::class)]
final class WithDedicatedConnectionTest extends TestCase
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
