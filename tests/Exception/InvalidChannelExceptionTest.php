<?php

declare(strict_types=1);

namespace Tourze\RedisDedicatedConnectionBundle\Tests\Exception;

use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\PHPUnitBase\AbstractExceptionTestCase;
use Tourze\RedisDedicatedConnectionBundle\Exception\InvalidChannelException;

/**
 * @internal
 */
#[CoversClass(InvalidChannelException::class)]
final class InvalidChannelExceptionTest extends AbstractExceptionTestCase
{
    public function testExceptionExtendsInvalidArgumentException(): void
    {
        $exception = new InvalidChannelException('Test message');

        $this->assertInstanceOf(\InvalidArgumentException::class, $exception);
    }

    public function testExceptionMessage(): void
    {
        $message = 'Invalid channel name';
        $exception = new InvalidChannelException($message);

        $this->assertEquals($message, $exception->getMessage());
    }

    public function testExceptionCode(): void
    {
        $message = 'Channel error';
        $code = 400;
        $exception = new InvalidChannelException($message, $code);

        $this->assertEquals($code, $exception->getCode());
    }

    public function testExceptionWithPrevious(): void
    {
        $previous = new \Exception('Previous exception');
        $exception = new InvalidChannelException('Wrapper exception', 0, $previous);

        $this->assertSame($previous, $exception->getPrevious());
    }

    public function testDefaultConstructor(): void
    {
        $exception = new InvalidChannelException();

        $this->assertEmpty($exception->getMessage());
        $this->assertEquals(0, $exception->getCode());
        $this->assertNull($exception->getPrevious());
    }
}
