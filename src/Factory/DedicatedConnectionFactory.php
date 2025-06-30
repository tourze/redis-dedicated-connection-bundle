<?php

namespace Tourze\RedisDedicatedConnectionBundle\Factory;

use Monolog\Attribute\WithMonologChannel;
use Psr\Log\LoggerInterface;
use Tourze\Symfony\RuntimeContextBundle\Service\ContextServiceInterface;
use Tourze\RedisDedicatedConnectionBundle\Exception\ConnectionCreationException;
use Tourze\RedisDedicatedConnectionBundle\Exception\InvalidChannelException;

/**
 * 专用 Redis 连接工厂
 * 负责创建和管理多个独立的 Redis 连接
 */
#[WithMonologChannel(channel: 'redis_factory')]
class DedicatedConnectionFactory
{
    private array $connections = [];

    public function __construct(
        private readonly ContextServiceInterface $contextService,
        private readonly LoggerInterface $logger,
    ) {
    }

    /**
     * 创建或获取专用的 Redis 连接
     * 在协程环境中，每个协程上下文都会有独立的连接池
     */
    public function createConnection(string $channel): \Redis
    {
        // 获取上下文相关的连接键
        $contextKey = $this->getContextKey($channel);

        if (isset($this->connections[$contextKey])) {
            return $this->connections[$contextKey];
        }

        $this->logger->debug('Creating dedicated Redis connection for channel: {channel} in context: {context}', [
            'channel' => $channel,
            'context' => $this->contextService->getId()
        ]);

        // 构建专用连接参数
        $params = $this->buildConnectionParams($channel);

        // 创建连接
        $connection = new \Redis();
        
        // 连接到 Redis 服务器
        if (!$this->connectRedis($connection, $params)) {
            throw new ConnectionCreationException(sprintf('Failed to connect to Redis for channel "%s"', $channel));
        }

        $this->connections[$contextKey] = $connection;

        // 在协程环境中，注册连接清理回调
        if ($this->contextService->supportCoroutine()) {
            $this->contextService->defer(function () use ($contextKey) {
                $this->closeConnection($contextKey);
            });
        }

        return $connection;
    }

    /**
     * 获取上下文相关的连接键
     */
    private function getContextKey(string $channel): string
    {
        if ($this->contextService->supportCoroutine()) {
            return $this->contextService->getId() . ':' . $channel;
        }

        return $channel;
    }

    /**
     * 构建连接参数
     */
    private function buildConnectionParams(string $channel): array
    {
        $envPrefix = strtoupper($channel);

        // 默认参数
        $params = [
            'host' => '127.0.0.1',
            'port' => 6379,
            'database' => 0,
            'timeout' => 5.0,
            'read_write_timeout' => 0.0,
            'persistent' => false,
            'auth' => null,
            'prefix' => null,
            'ssl' => false,
        ];

        // 首先检查 REDIS_URL 环境变量
        $redisUrlEnv = "{$envPrefix}_REDIS_URL";
        if (isset($_ENV[$redisUrlEnv])) {
            $urlParams = $this->parseRedisUrl($_ENV[$redisUrlEnv]);
            $params = array_merge($params, $urlParams);
        } elseif (isset($_ENV['REDIS_URL']) && $channel === 'default') {
            // 如果是默认频道，且没有特定的 URL，使用全局 REDIS_URL
            $urlParams = $this->parseRedisUrl($_ENV['REDIS_URL']);
            $params = array_merge($params, $urlParams);
        }

        // 支持通过环境变量覆盖（优先级高于 URL）
        $envMappings = [
            'host' => 'REDIS_HOST',
            'port' => 'REDIS_PORT',
            'database' => 'REDIS_DB',
            'auth' => 'REDIS_PASSWORD',
            'timeout' => 'REDIS_TIMEOUT',
            'read_write_timeout' => 'REDIS_READ_WRITE_TIMEOUT',
            'persistent' => 'REDIS_PERSISTENT',
            'prefix' => 'REDIS_PREFIX',
        ];

        foreach ($envMappings as $param => $envSuffix) {
            $envVar = "{$envPrefix}_{$envSuffix}";
            if (isset($_ENV[$envVar])) {
                $value = $_ENV[$envVar];

                // 类型转换
                switch ($param) {
                    case 'port':
                    case 'database':
                        $value = (int)$value;
                        break;
                    case 'timeout':
                    case 'read_write_timeout':
                        $value = (float)$value;
                        break;
                    case 'persistent':
                        $value = filter_var($value, FILTER_VALIDATE_BOOLEAN);
                        break;
                }

                $params[$param] = $value;
            }
        }

        return $params;
    }

    /**
     * 解析 Redis URL
     * 支持格式：
     * - redis://[[username:]password@]host[:port][/database][?query]
     * - rediss://[[username:]password@]host[:port][/database][?query]
     */
    private function parseRedisUrl(string $url): array
    {
        $parsed = parse_url($url);
        if ($parsed === false) {
            throw new InvalidChannelException(sprintf('Invalid Redis URL: %s', $url));
        }

        $params = [];

        // 检查协议
        if (isset($parsed['scheme'])) {
            if ($parsed['scheme'] === 'rediss') {
                $params['ssl'] = true;
            } elseif ($parsed['scheme'] !== 'redis') {
                throw new InvalidChannelException(sprintf('Invalid Redis URL scheme: %s', $parsed['scheme']));
            }
        }

        // 主机和端口
        if (isset($parsed['host'])) {
            $params['host'] = $parsed['host'];
        }
        if (isset($parsed['port'])) {
            $params['port'] = (int)$parsed['port'];
        }

        // 认证信息
        if (isset($parsed['pass'])) {
            $params['auth'] = $parsed['pass'];
        } elseif (isset($parsed['user']) && $parsed['user'] !== '') {
            // Redis 6+ ACL 支持 username:password
            if (isset($parsed['pass'])) {
                $params['auth'] = [$parsed['user'], $parsed['pass']];
            }
        }

        // 数据库编号（从路径中提取）
        if (isset($parsed['path'])) {
            $path = ltrim($parsed['path'], '/');
            if ($path !== '' && is_numeric($path)) {
                $params['database'] = (int)$path;
            }
        }

        // 查询参数
        if (isset($parsed['query'])) {
            parse_str($parsed['query'], $queryParams);
            
            // 处理已知的查询参数
            if (isset($queryParams['timeout'])) {
                $params['timeout'] = (float)$queryParams['timeout'];
            }
            if (isset($queryParams['read_write_timeout'])) {
                $params['read_write_timeout'] = (float)$queryParams['read_write_timeout'];
            }
            if (isset($queryParams['persistent'])) {
                $params['persistent'] = filter_var($queryParams['persistent'], FILTER_VALIDATE_BOOLEAN);
            }
            if (isset($queryParams['prefix'])) {
                $params['prefix'] = $queryParams['prefix'];
            }
        }

        return $params;
    }

    /**
     * 连接到 Redis 服务器
     */
    private function connectRedis(\Redis $redis, array $params): bool
    {
        try {
            // 处理 SSL 连接
            $host = $params['host'];
            if ($params['ssl']) {
                $host = 'tls://' . $host;
            }

            // 建立连接
            if ($params['persistent']) {
                $connected = @$redis->pconnect(
                    $host,
                    $params['port'],
                    $params['timeout'],
                    null,
                    100,
                    $params['read_write_timeout']
                );
            } else {
                $connected = @$redis->connect(
                    $host,
                    $params['port'],
                    $params['timeout'],
                    null,
                    100,
                    $params['read_write_timeout']
                );
            }

            if (!$connected) {
                return false;
            }

            // 认证
            if (!empty($params['auth'])) {
                // 支持 Redis 6+ ACL (username + password)
                if (is_array($params['auth']) && count($params['auth']) === 2) {
                    if (!$redis->auth($params['auth'])) {
                        return false;
                    }
                } else {
                    // 传统密码认证
                    if (!$redis->auth($params['auth'])) {
                        return false;
                    }
                }
            }

            // 选择数据库
            if ($params['database'] !== 0) {
                if (!$redis->select($params['database'])) {
                    return false;
                }
            }

            // 设置选项
            $redis->setOption(\Redis::OPT_SERIALIZER, \Redis::SERIALIZER_PHP);

            // 设置前缀
            if (!empty($params['prefix'])) {
                $redis->setOption(\Redis::OPT_PREFIX, $params['prefix']);
            }

            return true;
        } catch (\Exception $e) {
            $this->logger->error('Redis connection failed: {error}', [
                'exception' => $e,
            ]);
            return false;
        }
    }

    /**
     * 关闭指定的连接
     */
    private function closeConnection(string $contextKey): void
    {
        if (isset($this->connections[$contextKey])) {
            $this->logger->debug('Closing dedicated Redis connection: {contextKey}', ['contextKey' => $contextKey]);
            $this->connections[$contextKey]->close();
            unset($this->connections[$contextKey]);
        }
    }

    /**
     * 获取所有已创建的连接
     */
    public function getConnections(): array
    {
        return $this->connections;
    }

    /**
     * 关闭当前上下文的所有连接
     */
    public function closeCurrentContext(): void
    {
        if ($this->contextService->supportCoroutine()) {
            $this->closeAll($this->contextService->getId());
        } else {
            $this->closeAll();
        }
    }

    /**
     * 关闭所有连接，或者只关闭当前上下文的连接
     */
    public function closeAll(?string $contextId = null): void
    {
        if ($contextId === null) {
            // 关闭所有连接
            foreach ($this->connections as $connection) {
                $connection->close();
            }
            $this->connections = [];
        } else {
            // 只关闭指定上下文的连接
            $prefix = $contextId . ':';
            $toClose = [];

            foreach (array_keys($this->connections) as $key) {
                if (str_starts_with($key, $prefix)) {
                    $toClose[] = $key;
                }
            }

            foreach ($toClose as $key) {
                $this->closeConnection($key);
            }
        }
    }
}