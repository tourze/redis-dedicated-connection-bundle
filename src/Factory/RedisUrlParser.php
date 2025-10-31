<?php

namespace Tourze\RedisDedicatedConnectionBundle\Factory;

use Tourze\RedisDedicatedConnectionBundle\Exception\InvalidChannelException;

/**
 * Redis URL 解析器
 * 负责解析 Redis 连接 URL 格式
 */
class RedisUrlParser
{
    /**
     * 解析 Redis URL
     * 支持格式：
     * - redis://[[username:]password@]host[:port][/database][?query]
     * - rediss://[[username:]password@]host[:port][/database][?query]
     * @return array<string, mixed>
     */
    public function parseUrl(string $url): array
    {
        $parsed = parse_url($url);
        if (false === $parsed) {
            throw new InvalidChannelException(sprintf('Invalid Redis URL: %s', $url));
        }

        $params = [];

        $params = $this->parseScheme($parsed, $params);
        $params = $this->parseHostAndPort($parsed, $params);
        $params = $this->parseAuthentication($parsed, $params);
        $params = $this->parseDatabase($parsed, $params);

        return $this->parseQueryParameters($parsed, $params);
    }

    /**
     * @param array<string, mixed> $parsed
     * @param array<string, mixed> $params
     * @return array<string, mixed>
     */
    private function parseScheme(array $parsed, array $params): array
    {
        if (!isset($parsed['scheme'])) {
            return $params;
        }

        $scheme = $parsed['scheme'];
        if (!is_string($scheme)) {
            return $params;
        }

        if ('rediss' === $scheme) {
            $params['ssl'] = true;
        } elseif ('redis' !== $scheme) {
            throw new InvalidChannelException(sprintf('Invalid Redis URL scheme: %s', $scheme));
        }

        return $params;
    }

    /**
     * @param array<string, mixed> $parsed
     * @param array<string, mixed> $params
     * @return array<string, mixed>
     */
    private function parseHostAndPort(array $parsed, array $params): array
    {
        if (isset($parsed['host']) && is_string($parsed['host'])) {
            $params['host'] = $parsed['host'];
        }

        if (isset($parsed['port']) && is_int($parsed['port'])) {
            $params['port'] = $parsed['port'];
        }

        return $params;
    }

    /**
     * @param array<string, mixed> $parsed
     * @param array<string, mixed> $params
     * @return array<string, mixed>
     */
    private function parseAuthentication(array $parsed, array $params): array
    {
        if (isset($parsed['user']) && '' !== $parsed['user'] && isset($parsed['pass'])) {
            // Redis 6+ ACL 支持 username:password
            $params['auth'] = [$parsed['user'], $parsed['pass']];
        } elseif (isset($parsed['pass'])) {
            $params['auth'] = $parsed['pass'];
        }

        return $params;
    }

    /**
     * @param array<string, mixed> $parsed
     * @param array<string, mixed> $params
     * @return array<string, mixed>
     */
    private function parseDatabase(array $parsed, array $params): array
    {
        if (!isset($parsed['path'])) {
            return $params;
        }

        $path = $parsed['path'];
        if (!is_string($path)) {
            return $params;
        }

        $path = ltrim($path, '/');
        if ('' !== $path && is_numeric($path)) {
            $params['database'] = (int) $path;
        }

        return $params;
    }

    /**
     * @param array<string, mixed> $parsed
     * @param array<string, mixed> $params
     * @return array<string, mixed>
     */
    private function parseQueryParameters(array $parsed, array $params): array
    {
        if (!isset($parsed['query'])) {
            return $params;
        }

        $query = $parsed['query'];
        if (!is_string($query)) {
            return $params;
        }

        parse_str($query, $queryParams);

        $params = $this->mapQueryParam($queryParams, 'timeout', $params, 'float');
        $params = $this->mapQueryParam($queryParams, 'read_write_timeout', $params, 'float');
        $params = $this->mapQueryParam($queryParams, 'persistent', $params, 'bool');

        return $this->mapQueryParam($queryParams, 'prefix', $params, 'string');
    }

    /**
     * @param array<int|string, array<mixed>|string> $queryParams
     * @param array<string, mixed> $params
     * @return array<string, mixed>
     */
    private function mapQueryParam(array $queryParams, string $key, array $params, string $type): array
    {
        if (!isset($queryParams[$key])) {
            return $params;
        }

        $value = $queryParams[$key];

        switch ($type) {
            case 'float':
                $params[$key] = (float) $value;
                break;
            case 'bool':
                $params[$key] = filter_var($value, FILTER_VALIDATE_BOOLEAN);
                break;
            default:
                $params[$key] = $value;
        }

        return $params;
    }
}
