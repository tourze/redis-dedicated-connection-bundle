<?php

declare(strict_types=1);

namespace Tourze\RedisDedicatedConnectionBundle\Tests\Exception;

use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\PHPUnitBase\AbstractExceptionTestCase;
use Tourze\RedisDedicatedConnectionBundle\Exception\NoAuthException;

/**
 * @internal
 */
#[CoversClass(NoAuthException::class)]
final class NoAuthExceptionTest extends AbstractExceptionTestCase
{
    public function testExceptionExtendsRuntimeException(): void
    {
        $exception = new NoAuthException('Test message');

        $this->assertInstanceOf(\RuntimeException::class, $exception);
    }

    public function testExceptionMessage(): void
    {
        $message = 'Authentication failed';
        $exception = new NoAuthException($message);

        $this->assertEquals($message, $exception->getMessage());
    }

    public function testExceptionCode(): void
    {
        $message = 'Auth error';
        $code = 401;
        $exception = new NoAuthException($message, $code);

        $this->assertEquals($code, $exception->getCode());
    }

    public function testExceptionWithPrevious(): void
    {
        $previous = new \Exception('Previous exception');
        $exception = new NoAuthException('Wrapper exception', 0, $previous);

        $this->assertSame($previous, $exception->getPrevious());
    }

    public function testDefaultConstructor(): void
    {
        $exception = new NoAuthException();

        $this->assertEmpty($exception->getMessage());
        $this->assertEquals(0, $exception->getCode());
        $this->assertNull($exception->getPrevious());
    }
}