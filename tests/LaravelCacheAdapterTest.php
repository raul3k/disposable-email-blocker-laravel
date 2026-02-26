<?php

declare(strict_types=1);

namespace Raul3k\DisposableBlocker\Laravel\Tests;

use BadMethodCallException;
use Illuminate\Support\Facades\Cache;
use Raul3k\DisposableBlocker\Laravel\Cache\LaravelCacheAdapter;

class LaravelCacheAdapterTest extends TestCase
{
    private LaravelCacheAdapter $adapter;

    protected function setUp(): void
    {
        parent::setUp();

        $this->adapter = new LaravelCacheAdapter(Cache::store('array'), 'test_prefix:');
    }

    public function testGetReturnsNullWhenKeyDoesNotExist(): void
    {
        $this->assertNull($this->adapter->get('nonexistent'));
    }

    public function testSetAndGetValue(): void
    {
        $this->adapter->set('key1', 'value1');

        $this->assertSame('value1', $this->adapter->get('key1'));
    }

    public function testSetWithTtl(): void
    {
        $result = $this->adapter->set('key2', 'value2', 3600);

        $this->assertTrue($result);
        $this->assertSame('value2', $this->adapter->get('key2'));
    }

    public function testSetWithoutTtlUsesForever(): void
    {
        $result = $this->adapter->set('key3', 'value3');

        $this->assertTrue($result);
        $this->assertSame('value3', $this->adapter->get('key3'));
    }

    public function testHasReturnsTrueWhenKeyExists(): void
    {
        $this->adapter->set('key4', 'value4');

        $this->assertTrue($this->adapter->has('key4'));
    }

    public function testHasReturnsFalseWhenKeyDoesNotExist(): void
    {
        $this->assertFalse($this->adapter->has('nonexistent'));
    }

    public function testDeleteRemovesKey(): void
    {
        $this->adapter->set('key5', 'value5');
        $this->adapter->delete('key5');

        $this->assertNull($this->adapter->get('key5'));
        $this->assertFalse($this->adapter->has('key5'));
    }

    public function testClearThrowsBadMethodCallException(): void
    {
        $this->expectException(BadMethodCallException::class);

        $this->adapter->clear();
    }

    public function testPrefixIsolatesKeys(): void
    {
        $adapter1 = new LaravelCacheAdapter(Cache::store('array'), 'prefix1:');
        $adapter2 = new LaravelCacheAdapter(Cache::store('array'), 'prefix2:');

        $adapter1->set('shared_key', 'value1');
        $adapter2->set('shared_key', 'value2');

        $this->assertSame('value1', $adapter1->get('shared_key'));
        $this->assertSame('value2', $adapter2->get('shared_key'));
    }

    public function testSetAndGetBooleanValues(): void
    {
        $this->adapter->set('bool_true', true);
        $this->adapter->set('bool_false', false);

        $this->assertTrue($this->adapter->get('bool_true'));
        $this->assertFalse($this->adapter->get('bool_false'));
    }

    public function testSetAndGetIntegerValues(): void
    {
        $this->adapter->set('int_key', 42);

        $this->assertSame(42, $this->adapter->get('int_key'));
    }
}
