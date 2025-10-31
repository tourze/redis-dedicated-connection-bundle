<?php

namespace Tourze\RedisDedicatedConnectionBundle\Factory;

use Monolog\Attribute\WithMonologChannel;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use Tourze\RedisDedicatedConnectionBundle\Exception\ConnectionCreationException;
use Tourze\RedisDedicatedConnectionBundle\Exception\NoAuthException;
use Tourze\Symfony\RuntimeContextBundle\Service\ContextServiceInterface;

/**
 * 专用 Redis 连接工厂
 * 负责创建和管理多个独立的 Redis 连接
 */
#[WithMonologChannel(channel: 'redis_factory')]
#[Autoconfigure(public: true)]
class DedicatedConnectionFactory
{
    /** @var array<string, \Redis> */
    private array $connections = [];

    public function __construct(
        private readonly ContextServiceInterface $contextService,
        private readonly LoggerInterface $logger,
        private readonly ConnectionParamsBuilder $paramsBuilder,
        private readonly RedisConnector $connector,
    ) {
    }

    /**
     * 创建或获取专用的 Redis 连接
     * 在协程环境中，每个协程上下文都会有独立的连接池
     */
    public function createConnection(string $channel): \Redis
    {
        $contextKey = $this->getContextKey($channel);

        if (isset($this->connections[$contextKey])) {
            return $this->connections[$contextKey];
        }

        $this->logConnectionCreation($channel);

        $connection = $this->establishConnection($channel);
        $this->connections[$contextKey] = $connection;

        $this->registerCleanupCallback($contextKey);

        return $connection;
    }

    private function getContextKey(string $channel): string
    {
        if ($this->contextService->supportCoroutine()) {
            return $this->contextService->getId() . ':' . $channel;
        }

        return $channel;
    }

    private function logConnectionCreation(string $channel): void
    {
        $this->logger->debug('Creating dedicated Redis connection for channel: {channel} in context: {context}', [
            'channel' => $channel,
            'context' => $this->contextService->getId(),
        ]);
    }

    private function establishConnection(string $channel): \Redis
    {
        $params = $this->paramsBuilder->buildParams($channel);
        $connection = new \Redis();

        try {
            $rs = $this->connector->connect($connection, $params);
        } catch (\Throwable $e) {
            if (str_contains($e->getMessage(), 'NOAUTH')) {
                $envKey = $this->paramsBuilder->buildEnvKey($channel);

                throw new NoAuthException("Redis认证失败，请检查是否有配置环境变量：{$envKey}", previous: $e);
            }
            throw $e;
        }

        if (!$rs) {
            throw new ConnectionCreationException(sprintf('Failed to connect to Redis for channel "%s"', $channel));
        }

        return $connection;
    }

    private function registerCleanupCallback(string $contextKey): void
    {
        if ($this->contextService->supportCoroutine()) {
            $this->contextService->defer(function () use ($contextKey): void {
                $this->closeConnection($contextKey);
            });
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
     * @return array<string, \Redis>
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
        if (null === $contextId) {
            $this->closeAllConnections();
        } else {
            $this->closeContextConnections($contextId);
        }
    }

    private function closeAllConnections(): void
    {
        foreach ($this->connections as $connection) {
            $connection->close();
        }
        $this->connections = [];
    }

    private function closeContextConnections(string $contextId): void
    {
        $prefix = $contextId . ':';
        $toClose = $this->findConnectionsWithPrefix($prefix);

        foreach ($toClose as $key) {
            $this->closeConnection($key);
        }
    }

    /**
     * @return array<string>
     */
    private function findConnectionsWithPrefix(string $prefix): array
    {
        $toClose = [];
        foreach (array_keys($this->connections) as $key) {
            if (str_starts_with($key, $prefix)) {
                $toClose[] = $key;
            }
        }

        return $toClose;
    }
}
