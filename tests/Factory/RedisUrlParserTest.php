<?php

declare(strict_types=1);

namespace Tourze\RedisDedicatedConnectionBundle\Tests\Factory;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Tourze\RedisDedicatedConnectionBundle\Exception\InvalidChannelException;
use Tourze\RedisDedicatedConnectionBundle\Factory\RedisUrlParser;

/**
 * @internal
 */
#[CoversClass(RedisUrlParser::class)]
final class RedisUrlParserTest extends TestCase
{
    private RedisUrlParser $parser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->parser = new RedisUrlParser();
    }

    public function testParseBasicRedisUrl(): void
    {
        $result = $this->parser->parseUrl('redis://localhost:6379');

        $this->assertEquals('localhost', $result['host']);
        $this->assertEquals(6379, $result['port']);
        $this->assertFalse($result['ssl'] ?? false);
    }

    public function testParseRedisUrlWithDatabase(): void
    {
        $result = $this->parser->parseUrl('redis://localhost:6379/5');

        $this->assertEquals('localhost', $result['host']);
        $this->assertEquals(6379, $result['port']);
        $this->assertEquals(5, $result['database']);
    }

    public function testParseRedisUrlWithAuth(): void
    {
        $result = $this->parser->parseUrl('redis://:password@localhost:6379');

        $this->assertEquals('localhost', $result['host']);
        $this->assertEquals('password', $result['auth']);
    }

    public function testParseRedisUrlWithUsernameAndPassword(): void
    {
        $result = $this->parser->parseUrl('redis://username:password@localhost:6379');

        $this->assertEquals('localhost', $result['host']);
        $this->assertIsArray($result['auth']);
        $this->assertEquals(['username', 'password'], $result['auth']);
    }

    public function testParseRedissUrl(): void
    {
        $result = $this->parser->parseUrl('rediss://localhost:6379');

        $this->assertEquals('localhost', $result['host']);
        $this->assertTrue($result['ssl']);
    }

    public function testParseRedisUrlWithQueryParams(): void
    {
        $result = $this->parser->parseUrl('redis://localhost:6379?timeout=10&persistent=true&prefix=test');

        $this->assertEquals(10.0, $result['timeout']);
        $this->assertTrue($result['persistent']);
        $this->assertEquals('test', $result['prefix']);
    }

    public function testInvalidUrl(): void
    {
        $this->expectException(InvalidChannelException::class);
        $this->parser->parseUrl('http://');
    }

    public function testInvalidScheme(): void
    {
        $this->expectException(InvalidChannelException::class);
        $this->parser->parseUrl('http://localhost:6379');
    }

    public function testParseUrl(): void
    {
        $result = $this->parser->parseUrl('redis://localhost:6379');

        // 验证解析结果的具体业务内容而非类型
        $this->assertSame('localhost', $result['host'], '主机名必须正确解析');
        $this->assertSame(6379, $result['port'], '端口必须正确解析');
        $this->assertArrayHasKey('host', $result);
        $this->assertArrayHasKey('port', $result);
    }
}
