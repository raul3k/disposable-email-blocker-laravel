<?php

declare(strict_types=1);

namespace Raul3k\DisposableBlocker\Laravel\Tests;

use PHPUnit\Framework\Attributes\RequiresPhpExtension;
use Raul3k\DisposableBlocker\Laravel\Models\DisposableDomain;

#[RequiresPhpExtension('pdo_sqlite')]
class DisposableDomainTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');
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

    public function testUsesConfiguredTableName(): void
    {
        $model = new DisposableDomain();

        $this->assertEquals('disposable_domains', $model->getTable());
    }

    public function testUsesCustomTableNameFromConfig(): void
    {
        config()->set('disposable-blocker.database.table', 'custom_table');

        $model = new DisposableDomain();

        $this->assertEquals('custom_table', $model->getTable());
    }

    public function testUsesDefaultConnectionWhenNotConfigured(): void
    {
        $model = new DisposableDomain();

        $this->assertNull($model->getConnectionName());
    }

    public function testUsesCustomConnectionFromConfig(): void
    {
        config()->set('disposable-blocker.database.connection', 'mysql');

        $model = new DisposableDomain();

        $this->assertEquals('mysql', $model->getConnectionName());
    }

    public function testFillableDomainAndSource(): void
    {
        $model = new DisposableDomain([
            'domain' => 'test.com',
            'source' => 'custom-source',
        ]);

        $this->assertEquals('test.com', $model->domain);
        $this->assertEquals('custom-source', $model->source);
    }

    public function testCreateAndRetrieve(): void
    {
        DisposableDomain::create([
            'domain' => 'example.com',
            'source' => 'test-source',
        ]);

        $found = DisposableDomain::where('domain', 'example.com')->first();

        $this->assertNotNull($found);
        $this->assertEquals('example.com', $found->domain);
        $this->assertEquals('test-source', $found->source);
        $this->assertNotNull($found->created_at);
        $this->assertNotNull($found->updated_at);
    }

    public function testSourceIsNullable(): void
    {
        $model = DisposableDomain::create([
            'domain' => 'nosource.com',
        ]);

        $this->assertNull($model->source);
    }
}
