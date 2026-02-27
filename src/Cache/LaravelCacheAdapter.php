<?php

declare(strict_types=1);

namespace Raul3k\DisposableBlocker\Laravel\Cache;

use BadMethodCallException;
use Illuminate\Contracts\Cache\Repository;
use Raul3k\DisposableBlocker\Core\Cache\CacheInterface;

/**
 * Laravel cache adapter for the disposable email checker.
 */
class LaravelCacheAdapter implements CacheInterface
{
    public function __construct(
        private readonly Repository $cache,
        private readonly string $prefix = 'disposable_email:'
    ) {}

    public function get(string $key): mixed
    {
        return $this->cache->get($this->prefix . $key);
    }

    public function set(string $key, mixed $value, ?int $ttl = null): bool
    {
        if ($ttl === null) {
            return $this->cache->forever($this->prefix . $key, $value);
        }

        return $this->cache->put($this->prefix . $key, $value, $ttl);
    }

    public function has(string $key): bool
    {
        return $this->cache->has($this->prefix . $key);
    }

    public function delete(string $key): bool
    {
        return $this->cache->forget($this->prefix . $key);
    }

    public function clear(): bool
    {
        throw new BadMethodCallException(
            'clear() is not supported on Laravel cache adapter because it would flush the entire cache store.'
        );
    }
}
