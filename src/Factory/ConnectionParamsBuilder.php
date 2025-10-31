<?php

namespace Tourze\RedisDedicatedConnectionBundle\Factory;

/**
 * Redis 连接参数构建器
 * 负责构建 Redis 连接的参数配置
 *
 * @phpstan-import-type ConnectionParams from RedisConnector
 */
readonly class ConnectionParamsBuilder
{
    public function __construct(
        private RedisUrlParser $urlParser,
    ) {
    }

    public function buildEnvKey(string $channel): string
    {
        return strtoupper($channel) . '_REDIS_URL';
    }

    /**
     * 构建连接参数
     * @phpstan-return ConnectionParams
     */
    public function buildParams(string $channel): array
    {
        $params = $this->getDefaultParams();
        $envPrefix = strtoupper($channel);

        $params = $this->applyUrlParams($params, $channel);

        return $this->applyEnvironmentOverrides($params, $envPrefix);
    }

    /**
     * @phpstan-return ConnectionParams
     */
    private function getDefaultParams(): array
    {
        return [
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
    }

    /**
     * @phpstan-param ConnectionParams $params
     * @phpstan-return ConnectionParams
     */
    private function applyUrlParams(array $params, string $channel): array
    {
        $redisUrlEnv = $this->buildEnvKey($channel);

        if (isset($_ENV[$redisUrlEnv])) {
            $url = $_ENV[$redisUrlEnv];
            if (is_string($url)) {
                $urlParams = $this->urlParser->parseUrl($url);
                /** @var ConnectionParams */
                return array_merge($params, $urlParams);
            }
        }
        if (isset($_ENV['REDIS_URL']) && 'default' === $channel) {
            // 如果是默认频道，且没有特定的 URL，使用全局 REDIS_URL
            $url = $_ENV['REDIS_URL'];
            if (is_string($url)) {
                $urlParams = $this->urlParser->parseUrl($url);
                /** @var ConnectionParams */
                return array_merge($params, $urlParams);
            }
        }

        return $params;
    }

    /**
     * @phpstan-param ConnectionParams $params
     * @phpstan-return ConnectionParams
     */
    private function applyEnvironmentOverrides(array $params, string $envPrefix): array
    {
        $envMappings = $this->getEnvironmentMappings();

        foreach ($envMappings as $param => $envSuffix) {
            $envVar = "{$envPrefix}_{$envSuffix}";
            if (isset($_ENV[$envVar])) {
                $value = $_ENV[$envVar];
                if (is_string($value)) {
                    $params[$param] = $this->convertEnvironmentValue($param, $value);
                }
            }
        }
        /** @var ConnectionParams */
        return $params;
    }

    /**
     * @return array<string, string>
     */
    private function getEnvironmentMappings(): array
    {
        return [
            'host' => 'REDIS_HOST',
            'port' => 'REDIS_PORT',
            'database' => 'REDIS_DB',
            'auth' => 'REDIS_PASSWORD',
            'timeout' => 'REDIS_TIMEOUT',
            'read_write_timeout' => 'REDIS_READ_WRITE_TIMEOUT',
            'persistent' => 'REDIS_PERSISTENT',
            'prefix' => 'REDIS_PREFIX',
        ];
    }

    private function convertEnvironmentValue(string $param, string $value): mixed
    {
        return match ($param) {
            'port', 'database' => (int) $value,
            'timeout', 'read_write_timeout' => (float) $value,
            'persistent' => filter_var($value, FILTER_VALIDATE_BOOLEAN),
            default => $value,
        };
    }
}
