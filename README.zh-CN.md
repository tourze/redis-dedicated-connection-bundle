# Redis 专用连接 Bundle

此 Bundle 为 Symfony 服务提供自动创建和注入专用 Redis 连接的功能，支持属性、标签和环境变量等多种配置方式。

## 功能特性

- **自动连接创建**：根据服务需求自动创建 Redis 连接
- **多种配置方式**：支持 PHP 属性、服务标签和环境变量
- **连接隔离**：每个服务可以拥有自己的专用 Redis 连接
- **协程支持**：在协程环境中正确管理连接
- **灵活配置**：支持单实例、集群和主从模式

## 安装

```bash
composer require tourze/redis-dedicated-connection-bundle
```

**注意**：此 Bundle 需要安装 PHP Redis 扩展：
```bash
pecl install redis
```

## 使用方法

### 方法一：使用 PHP 属性

```php
use Tourze\RedisDedicatedConnectionBundle\Attribute\WithDedicatedConnection;

#[WithDedicatedConnection('cache')]
class CacheService
{
    public function __construct(
        private readonly \Redis $redis
    ) {}
}
```

### 方法二：使用服务标签

```yaml
services:
  App\Service\SessionService:
    arguments:
      $redis: '@redis'
    tags:
      - { name: 'redis.dedicated_connection', channel: 'session' }
```

### 方法三：使用连接通道标签

```yaml
services:
  App\Service\QueueService:
    calls:
      - [setRedis, ['@redis']]
    tags:
      - { name: 'redis.connection_channel', channel: 'queue' }
```

### 方法四：使用辅助类

```php
use Tourze\RedisDedicatedConnectionBundle\DependencyInjection\DedicatedConnectionHelper;

// 在你的 bundle 扩展或编译器传递中
$definition = $container->getDefinition('app.my_service');
DedicatedConnectionHelper::addDedicatedConnection($definition, 'metrics');

// 或创建一个带连接的新服务
$definition = DedicatedConnectionHelper::createServiceWithConnection(
    MyService::class,
    'analytics',
    ['@logger'] // 额外参数
);
$container->setDefinition('app.analytics_service', $definition);
```

## 配置

### 环境变量

使用模式 `{CHANNEL}_REDIS_{OPTION}` 的环境变量配置 Redis 连接：

```env
# 基本配置
CACHE_REDIS_HOST=localhost
CACHE_REDIS_PORT=6379
CACHE_REDIS_DB=0
CACHE_REDIS_PASSWORD=secret
CACHE_REDIS_PREFIX=myapp:cache:

# 高级选项
CACHE_REDIS_TIMEOUT=5.0
CACHE_REDIS_READ_WRITE_TIMEOUT=0.0
CACHE_REDIS_PERSISTENT=true
```


## 可用的环境变量

对于每个通道，你可以配置：

- `{CHANNEL}_REDIS_HOST`：Redis 主机（默认：127.0.0.1）
- `{CHANNEL}_REDIS_PORT`：Redis 端口（默认：6379）
- `{CHANNEL}_REDIS_DB`：数据库编号（默认：0）
- `{CHANNEL}_REDIS_PASSWORD`：连接密码
- `{CHANNEL}_REDIS_TIMEOUT`：连接超时秒数（默认：5.0）
- `{CHANNEL}_REDIS_READ_WRITE_TIMEOUT`：读写超时（默认：0.0）
- `{CHANNEL}_REDIS_PERSISTENT`：使用持久连接（默认：false）
- `{CHANNEL}_REDIS_PREFIX`：所有操作的键前缀

## 高级用法

### 为一个服务配置多个连接

```php
use Tourze\RedisDedicatedConnectionBundle\DependencyInjection\DedicatedConnectionHelper;

// 在你的服务配置中
$definition = $container->getDefinition('app.multi_redis_service');
DedicatedConnectionHelper::addMultipleDedicatedConnections(
    $definition,
    ['cache', 'session', 'queue']
);
```

### 直接引用连接

```yaml
services:
  App\Service\CustomService:
    arguments:
      $cacheRedis: '@redis.cache_connection'
      $sessionRedis: '@redis.session_connection'
```

### 检查连接是否存在

```php
use Tourze\RedisDedicatedConnectionBundle\DependencyInjection\DedicatedConnectionHelper;

if (DedicatedConnectionHelper::hasConnection($container, 'cache')) {
    // 连接存在
}
```

## 测试

运行测试套件：

```bash
# 单元测试
vendor/bin/phpunit tests/Unit

# 集成测试
vendor/bin/phpunit tests/Integration

# 所有测试
vendor/bin/phpunit
```

## 许可证

此 Bundle 基于 MIT 许可证发布。
