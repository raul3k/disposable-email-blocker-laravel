<?php

declare(strict_types=1);

namespace Raul3k\DisposableBlocker\Laravel\Tests;

use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\RequiresPhpExtension;

#[RequiresPhpExtension('pdo_sqlite')]
class UpdateDomainsCommandTest extends TestCase
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

    public function testCommandExitsWithSuccess(): void
    {
        $this->artisan('disposable:update')
            ->assertExitCode(0);
    }

    public function testCommandInsertsDomainsIntoDatabase(): void
    {
        $this->artisan('disposable:update');

        $count = DB::table('disposable_domains')->count();
        $this->assertGreaterThan(0, $count);
    }

    public function testCommandWithSpecificSource(): void
    {
        $this->artisan('disposable:update', ['--source' => 'disposable-email-domains'])
            ->assertExitCode(0);

        $count = DB::table('disposable_domains')
            ->where('source', 'disposable-email-domains')
            ->count();

        $this->assertGreaterThan(0, $count);
    }

    public function testCommandWithUnknownSourceOutputsError(): void
    {
        $this->artisan('disposable:update', ['--source' => 'nonexistent-source'])
            ->expectsOutputToContain('not found')
            ->assertExitCode(1);
    }

    public function testCommandWithCustomChunkSize(): void
    {
        $this->artisan('disposable:update', [
            '--source' => 'disposable-email-domains',
            '--chunk' => '500',
        ])->assertExitCode(0);
    }

    public function testCommandRerunPerformsUpsertWithoutError(): void
    {
        $this->artisan('disposable:update', ['--source' => 'disposable-email-domains']);
        $firstCount = DB::table('disposable_domains')->count();

        $this->artisan('disposable:update', ['--source' => 'disposable-email-domains']);
        $secondCount = DB::table('disposable_domains')->count();

        $this->assertSame($firstCount, $secondCount);
    }
}
