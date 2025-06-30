<?php

declare(strict_types=1);

namespace Tourze\RedisDedicatedConnectionBundle\Tests\Unit\Exception;

use PHPUnit\Framework\TestCase;
use Tourze\RedisDedicatedConnectionBundle\Exception\ConnectionCreationException;

class ConnectionCreationExceptionTest extends TestCase
{
    public function testExceptionExtendsRuntimeException(): void
    {
        $exception = new ConnectionCreationException('Test message');
        
        $this->assertInstanceOf(\RuntimeException::class, $exception);
    }

    public function testExceptionMessage(): void
    {
        $message = 'Failed to create connection';
        $exception = new ConnectionCreationException($message);
        
        $this->assertEquals($message, $exception->getMessage());
    }

    public function testExceptionCode(): void
    {
        $message = 'Connection error';
        $code = 500;
        $exception = new ConnectionCreationException($message, $code);
        
        $this->assertEquals($code, $exception->getCode());
    }

    public function testExceptionWithPrevious(): void
    {
        $previous = new \Exception('Previous exception');
        $exception = new ConnectionCreationException('Wrapper exception', 0, $previous);
        
        $this->assertSame($previous, $exception->getPrevious());
    }

    public function testDefaultConstructor(): void
    {
        $exception = new ConnectionCreationException();
        
        $this->assertEmpty($exception->getMessage());
        $this->assertEquals(0, $exception->getCode());
        $this->assertNull($exception->getPrevious());
    }
}