<?php

namespace Tourze\RedisDedicatedConnectionBundle\Factory;

use Psr\Log\LoggerInterface;

/**
 * Redis 连接器
 * 负责实际连接到 Redis 服务器并配置连接选项
 *
 * @phpstan-type ConnectionParams array{
 *     host: string,
 *     port: int,
 *     database: int,
 *     timeout: float,
 *     read_write_timeout: float,
 *     persistent: bool,
 *     auth: null|string|array{0: string, 1: string},
 *     prefix: null|string,
 *     ssl: bool
 * }
 */
class RedisConnector
{
    public function __construct(
        private readonly LoggerInterface $logger,
    ) {
    }

    /**
     * 连接到 Redis 服务器
     * @phpstan-param ConnectionParams $params
     */
    public function connect(\Redis $redis, array $params): bool
    {
        try {
            if (!$this->establishConnection($redis, $params)) {
                return false;
            }

            if (!$this->authenticate($redis, $params)) {
                return false;
            }

            if (!$this->selectDatabase($redis, $params)) {
                return false;
            }

            $this->configureOptions($redis, $params);

            return true;
        } catch (\Exception $e) {
            $this->logger->error('Redis connection failed: {error}', [
                'exception' => $e,
            ]);

            return false;
        }
    }

    /**
     * @phpstan-param ConnectionParams $params
     */
    private function establishConnection(\Redis $redis, array $params): bool
    {
        $host = $this->prepareHost($params);

        if ($params['persistent']) {
            return @$redis->pconnect(
                $host,
                $params['port'],
                $params['timeout'],
                null,
                100,
                $params['read_write_timeout']
            );
        }

        return @$redis->connect(
            $host,
            $params['port'],
            $params['timeout'],
            null,
            100,
            $params['read_write_timeout']
        );
    }

    /**
     * @phpstan-param ConnectionParams $params
     */
    private function prepareHost(array $params): string
    {
        if ($params['ssl']) {
            return 'tls://' . $params['host'];
        }

        return $params['host'];
    }

    /**
     * @phpstan-param ConnectionParams $params
     */
    private function authenticate(\Redis $redis, array $params): bool
    {
        if (!isset($params['auth']) || '' === $params['auth']) {
            return true;
        }

        // 支持 Redis 6+ ACL (username + password)
        if (is_array($params['auth'])) {
            return $redis->auth($params['auth']);
        }

        // 传统密码认证
        return $redis->auth($params['auth']);
    }

    /**
     * @phpstan-param ConnectionParams $params
     */
    private function selectDatabase(\Redis $redis, array $params): bool
    {
        if (0 === $params['database']) {
            return true;
        }

        return $redis->select($params['database']);
    }

    /**
     * @phpstan-param ConnectionParams $params
     */
    private function configureOptions(\Redis $redis, array $params): void
    {
        $redis->setOption(\Redis::OPT_SERIALIZER, \Redis::SERIALIZER_PHP);

        if (isset($params['prefix']) && '' !== $params['prefix']) {
            $redis->setOption(\Redis::OPT_PREFIX, $params['prefix']);
        }
    }
}
