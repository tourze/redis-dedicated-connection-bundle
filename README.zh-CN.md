# Redis 专用连接 Bundle

[English](README.md) | [中文](README.zh-CN.md)

[![Latest Version](https://img.shields.io/packagist/v/tourze/redis-dedicated-connection-bundle.svg?style=flat-square)](https://packagist.org/packages/tourze/redis-dedicated-connection-bundle)
[![PHP Version Require](https://poser.pugx.org/tourze/redis-dedicated-connection-bundle/require/php?style=flat-square)](https://packagist.org/packages/tourze/redis-dedicated-connection-bundle)
[![Build Status](https://img.shields.io/github/actions/workflow/status/tourze/php-monorepo/ci.yml?branch=master&style=flat-square)](https://github.com/tourze/php-monorepo/actions)
[![Quality Score](https://img.shields.io/scrutinizer/g/tourze/php-monorepo.svg?style=flat-square)](https://scrutinizer-ci.com/g/tourze/php-monorepo)
[![Code Coverage](https://img.shields.io/scrutinizer/coverage/g/tourze/php-monorepo?style=flat-square)](https://scrutinizer-ci.com/g/tourze/php-monorepo)
[![License](https://img.shields.io/github/license/tourze/php-monorepo?style=flat-square)](LICENSE)
[![Total Downloads](https://img.shields.io/packagist/dt/tourze/redis-dedicated-connection-bundle.svg?style=flat-square)](https://packagist.org/packages/tourze/redis-dedicated-connection-bundle)

此 Bundle 为 Symfony 服务提供自动创建和注入专用 Redis 连接的功能，支持属性、标签和环境变量等多种配置方式。

## 目录

- [功能特性](#功能特性)
- [依赖关系](#依赖关系)
- [安装](#安装)
- [使用方法](#使用方法)
  - [方法一：使用 PHP 属性](#方法一使用-php-属性)
  - [方法二：使用服务标签](#方法二使用服务标签)
  - [方法三：使用连接通道标签](#方法三使用连接通道标签)
  - [方法四：使用辅助类](#方法四使用辅助类)
- [配置](#配置)
  - [环境变量](#环境变量)
  - [方法一：使用 Redis URL（推荐）](#方法一使用-redis-url推荐)
  - [方法二：使用独立参数](#方法二使用独立参数)
- [可用的环境变量](#可用的环境变量)
- [高级用法](#高级用法)
  - [为一个服务配置多个连接](#为一个服务配置多个连接)
  - [直接引用连接](#直接引用连接)
  - [检查连接是否存在](#检查连接是否存在)
- [测试](#测试)
- [安全性](#安全性)
- [贡献](#贡献)
  - [运行测试](#运行测试)
  - [报告问题](#报告问题)
- [许可证](#许可证)

## 功能特性

- **自动连接创建**：根据服务需求自动创建 Redis 连接
- **多种配置方式**：支持 PHP 属性、服务标签和环境变量
- **连接隔离**：每个服务可以拥有自己的专用 Redis 连接
- **协程支持**：在协程环境中正确管理连接
- **灵活配置**：支持单实例、集群和主从模式

## 依赖关系

- PHP 8.1 或更高版本
- Symfony 6.4 或更高版本
- PHP Redis 扩展
- tourze/symfony-runtime-context-bundle

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

Bundle 支持两种配置 Redis 连接的方式：

#### 方法一：使用 Redis URL（推荐）

使用 Redis URL 配置连接，遵循[官方 Redis URI 规范](https://github.com/redis/redis-specifications/blob/master/uri/redis.txt)：

```env
# 默认连接
REDIS_URL=redis://127.0.0.1:6379/0

# 带认证
REDIS_URL=redis://password@localhost:6379/0

# 使用 Redis 6+ ACL（用户名和密码）
REDIS_URL=redis://username:password@localhost:6379/0

# 带查询参数
REDIS_URL=redis://localhost:6379/0?timeout=10&prefix=app:

# SSL/TLS 连接（rediss://）
REDIS_URL=rediss://secure.redis.host:6380/0

# 特定通道的 URL
CACHE_REDIS_URL=redis://cache.redis.local:6379/1?prefix=cache:
SESSION_REDIS_URL=redis://session.redis.local:6379/2?prefix=session:
QUEUE_REDIS_URL=redis://queue.redis.local:6379/3?persistent=true
```

URL 中支持的查询参数：
- `timeout` - 连接超时秒数
- `read_write_timeout` - 读写超时秒数
- `persistent` - 使用持久连接（true/false）
- `prefix` - 所有操作的键前缀

#### 方法二：使用独立参数

使用模式 `{CHANNEL}_REDIS_{OPTION}` 的环境变量配置连接：

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

**注意**：如果同时指定了独立参数和 URL 参数，独立参数优先级更高。

## 可用的环境变量

对于每个通道，你可以配置：

- `{CHANNEL}_REDIS_URL`：Redis 连接 URL（设置后优先使用）
- `{CHANNEL}_REDIS_HOST`：Redis 主机（默认：127.0.0.1）
- `{CHANNEL}_REDIS_PORT`：Redis 端口（默认：6379）
- `{CHANNEL}_REDIS_DB`：数据库编号（默认：0）
- `{CHANNEL}_REDIS_PASSWORD`：连接密码
- `{CHANNEL}_REDIS_TIMEOUT`：连接超时秒数（默认：5.0）
- `{CHANNEL}_REDIS_READ_WRITE_TIMEOUT`：读写超时（默认：0.0）
- `{CHANNEL}_REDIS_PERSISTENT`：使用持久连接（默认：false）
- `{CHANNEL}_REDIS_PREFIX`：所有操作的键前缀

对于默认通道，你也可以使用全局的 `REDIS_URL` 环境变量。

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
# 从 monorepo 根目录运行
./vendor/bin/phpunit packages/redis-dedicated-connection-bundle/tests
```

**注意**：集成测试需要运行中的 Redis 服务器。如果 Redis 不可用，这些测试将被跳过。

## 安全性

### 安全考虑

使用此 Bundle 时，请考虑以下安全方面：

1. **环境变量**：永远不要将 Redis 凭据提交到版本控制中。使用环境变量或安全的配置管理系统。

2. **网络安全**：
    - 在生产环境中使用 SSL/TLS 连接（rediss://）
    - 仅限可信网络访问 Redis 服务器
    - 正确配置 Redis 认证和 ACL

3. **数据加密**：此 Bundle 不在应用层加密数据。考虑在存储到 Redis 之前加密敏感数据。

4. **连接限制**：配置适当的连接限制以防止资源耗尽攻击。

### 报告安全漏洞

如果您发现安全漏洞，请发送邮件至 security@tourze.com，而不是创建公开问题。所有安全漏洞都将得到及时处理。

## 贡献

我们欢迎贡献！请查看我们的[贡献指南](../../CONTRIBUTING.md)了解详情。

### 运行测试

在提交拉取请求之前，请确保：

1. 所有测试通过：`./vendor/bin/phpunit packages/redis-dedicated-connection-bundle/tests`
2. 代码遵循 PSR 标准：`./vendor/bin/php-cs-fixer fix packages/redis-dedicated-connection-bundle`
3. 静态分析通过：`./vendor/bin/phpstan analyse packages/redis-dedicated-connection-bundle`

### 报告问题

如果您发现错误或有功能请求，请在我们的 [GitHub 仓库](https://github.com/tourze/php-monorepo/issues)上创建问题。

## 许可证

此 Bundle 基于 MIT 许可证发布。详情请查看 [LICENSE](../../LICENSE) 文件。
