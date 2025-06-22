# Redis Dedicated Connection Bundle

This bundle provides automatic creation and injection of dedicated Redis connections for Symfony services, supporting attributes, tags, and environment variable configuration.

## Features

- **Automatic Connection Creation**: Automatically creates Redis connections based on service requirements
- **Multiple Configuration Methods**: Support for PHP attributes, service tags, and environment variables
- **Connection Isolation**: Each service can have its own dedicated Redis connection
- **Coroutine Support**: Proper connection management in coroutine environments
- **Flexible Configuration**: Support for single instance, cluster, and replication modes

## Installation

```bash
composer require tourze/redis-dedicated-connection-bundle
```

**Note**: This bundle requires the PHP Redis extension to be installed:
```bash
pecl install redis
```

## Usage

### Method 1: Using PHP Attributes

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

### Method 2: Using Service Tags

```yaml
services:
  App\Service\SessionService:
    arguments:
      $redis: '@redis'
    tags:
      - { name: 'redis.dedicated_connection', channel: 'session' }
```

### Method 3: Using Connection Channel Tags

```yaml
services:
  App\Service\QueueService:
    calls:
      - [setRedis, ['@redis']]
    tags:
      - { name: 'redis.connection_channel', channel: 'queue' }
```

### Method 4: Using the Helper Class

```php
use Tourze\RedisDedicatedConnectionBundle\DependencyInjection\DedicatedConnectionHelper;

// In your bundle extension or compiler pass
$definition = $container->getDefinition('app.my_service');
DedicatedConnectionHelper::addDedicatedConnection($definition, 'metrics');

// Or create a new service with connection
$definition = DedicatedConnectionHelper::createServiceWithConnection(
    MyService::class,
    'analytics',
    ['@logger'] // additional arguments
);
$container->setDefinition('app.analytics_service', $definition);
```

## Configuration

### Environment Variables

The bundle supports two ways to configure Redis connections:

#### Method 1: Using Redis URL (Recommended)

Configure connections using Redis URLs following the [official Redis URI scheme](https://github.com/redis/redis-specifications/blob/master/uri/redis.txt):

```env
# Default connection
REDIS_URL=redis://127.0.0.1:6379/0

# With authentication
REDIS_URL=redis://password@localhost:6379/0

# With Redis 6+ ACL (username and password)
REDIS_URL=redis://username:password@localhost:6379/0

# With query parameters
REDIS_URL=redis://localhost:6379/0?timeout=10&prefix=app:

# SSL/TLS connection (rediss://)
REDIS_URL=rediss://secure.redis.host:6380/0

# Channel-specific URLs
CACHE_REDIS_URL=redis://cache.redis.local:6379/1?prefix=cache:
SESSION_REDIS_URL=redis://session.redis.local:6379/2?prefix=session:
QUEUE_REDIS_URL=redis://queue.redis.local:6379/3?persistent=true
```

Supported query parameters in URLs:
- `timeout` - Connection timeout in seconds
- `read_write_timeout` - Read/write timeout in seconds
- `persistent` - Use persistent connection (true/false)
- `prefix` - Key prefix for all operations

#### Method 2: Using Individual Parameters

Configure connections using individual environment variables with the pattern `{CHANNEL}_REDIS_{OPTION}`:

```env
# Basic configuration
CACHE_REDIS_HOST=localhost
CACHE_REDIS_PORT=6379
CACHE_REDIS_DB=0
CACHE_REDIS_PASSWORD=secret
CACHE_REDIS_PREFIX=myapp:cache:

# Advanced options
CACHE_REDIS_TIMEOUT=5.0
CACHE_REDIS_READ_WRITE_TIMEOUT=0.0
CACHE_REDIS_PERSISTENT=true
```

**Note**: Individual parameters take precedence over URL parameters if both are specified.

## Available Environment Variables

For each channel, you can configure:

- `{CHANNEL}_REDIS_URL`: Redis connection URL (takes precedence if set)
- `{CHANNEL}_REDIS_HOST`: Redis host (default: 127.0.0.1)
- `{CHANNEL}_REDIS_PORT`: Redis port (default: 6379)
- `{CHANNEL}_REDIS_DB`: Database number (default: 0)
- `{CHANNEL}_REDIS_PASSWORD`: Connection password
- `{CHANNEL}_REDIS_TIMEOUT`: Connection timeout in seconds (default: 5.0)
- `{CHANNEL}_REDIS_READ_WRITE_TIMEOUT`: Read/write timeout (default: 0.0)
- `{CHANNEL}_REDIS_PERSISTENT`: Use persistent connections (default: false)
- `{CHANNEL}_REDIS_PREFIX`: Key prefix for all operations

For the default channel, you can also use the global `REDIS_URL` environment variable.

## Advanced Usage

### Multiple Connections for One Service

```php
use Tourze\RedisDedicatedConnectionBundle\DependencyInjection\DedicatedConnectionHelper;

// In your service configuration
$definition = $container->getDefinition('app.multi_redis_service');
DedicatedConnectionHelper::addMultipleDedicatedConnections(
    $definition,
    ['cache', 'session', 'queue']
);
```

### Direct Connection Reference

```yaml
services:
  App\Service\CustomService:
    arguments:
      $cacheRedis: '@redis.cache_connection'
      $sessionRedis: '@redis.session_connection'
```

### Checking Connection Existence

```php
use Tourze\RedisDedicatedConnectionBundle\DependencyInjection\DedicatedConnectionHelper;

if (DedicatedConnectionHelper::hasConnection($container, 'cache')) {
    // Connection exists
}
```

## Testing

Run the test suite:

```bash
# Unit tests
vendor/bin/phpunit tests/Unit

# Integration tests
vendor/bin/phpunit tests/Integration

# All tests
vendor/bin/phpunit
```

## License

This bundle is licensed under the MIT License.