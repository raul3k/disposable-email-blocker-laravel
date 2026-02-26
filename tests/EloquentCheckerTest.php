<?php

declare(strict_types=1);

namespace Raul3k\DisposableBlocker\Laravel\Tests;

use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\RequiresPhpExtension;
use Raul3k\DisposableBlocker\Laravel\Checkers\EloquentChecker;
use Raul3k\DisposableBlocker\Laravel\Models\DisposableDomain;

#[RequiresPhpExtension('pdo_sqlite')]
class EloquentCheckerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->setupDatabase();
    }

    protected function defineEnvironment($app): void
    {
        parent::defineEnvironment($app);

        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);
    }

    private function setupDatabase(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');
    }

    public function testIsDomainDisposableReturnsFalseWhenTableIsEmpty(): void
    {
        $checker = new EloquentChecker('disposable_domains');

        $this->assertFalse($checker->isDomainDisposable('test.com'));
    }

    public function testIsDomainDisposableReturnsTrueWhenDomainExists(): void
    {
        DisposableDomain::create([
            'domain' => 'disposable.com',
            'source' => 'test',
        ]);

        $checker = new EloquentChecker('disposable_domains');

        $this->assertTrue($checker->isDomainDisposable('disposable.com'));
    }

    public function testIsDomainDisposableReturnsFalseWhenDomainDoesNotExist(): void
    {
        DisposableDomain::create([
            'domain' => 'disposable.com',
            'source' => 'test',
        ]);

        $checker = new EloquentChecker('disposable_domains');

        $this->assertFalse($checker->isDomainDisposable('gmail.com'));
    }

    public function testGetAllDomainsReturnsAllDomains(): void
    {
        DisposableDomain::create(['domain' => 'domain1.com', 'source' => 'test']);
        DisposableDomain::create(['domain' => 'domain2.com', 'source' => 'test']);
        DisposableDomain::create(['domain' => 'domain3.com', 'source' => 'test']);

        $checker = new EloquentChecker('disposable_domains');
        $domains = $checker->getAllDomains();

        $this->assertCount(3, $domains);
        $this->assertContains('domain1.com', $domains);
        $this->assertContains('domain2.com', $domains);
        $this->assertContains('domain3.com', $domains);
    }

    public function testCountReturnsCorrectCount(): void
    {
        DisposableDomain::create(['domain' => 'domain1.com', 'source' => 'test']);
        DisposableDomain::create(['domain' => 'domain2.com', 'source' => 'test']);

        $checker = new EloquentChecker('disposable_domains');

        $this->assertEquals(2, $checker->count());
    }

    public function testCountReturnsZeroWhenTableIsEmpty(): void
    {
        $checker = new EloquentChecker('disposable_domains');

        $this->assertEquals(0, $checker->count());
    }

    public function testDomainUniqueness(): void
    {
        DisposableDomain::create(['domain' => 'unique.com', 'source' => 'test']);

        $this->expectException(\Illuminate\Database\QueryException::class);

        DisposableDomain::create(['domain' => 'unique.com', 'source' => 'test']);
    }
}
