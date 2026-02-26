<?php

declare(strict_types=1);

namespace Raul3k\DisposableBlocker\Laravel\Tests;

use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\RequiresPhpExtension;

#[RequiresPhpExtension('pdo_sqlite')]
class ImportDomainsCommandTest extends TestCase
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

    public function testCommandExitsWithSuccessForKnownSource(): void
    {
        $this->artisan('disposable:import', ['source' => 'disposable-email-domains'])
            ->assertExitCode(0);
    }

    public function testCommandExitsWithFailureForUnknownSource(): void
    {
        $this->artisan('disposable:import', ['source' => 'nonexistent-source'])
            ->expectsOutputToContain('not found')
            ->assertExitCode(1);
    }

    public function testCommandImportsDomainsIntoDatabase(): void
    {
        $this->artisan('disposable:import', ['source' => 'disposable-email-domains']);

        $count = DB::table('disposable_domains')->count();
        $this->assertGreaterThan(0, $count);
    }

    public function testCommandWithClearFlagDeletesExistingDomains(): void
    {
        DB::table('disposable_domains')->insert([
            'domain' => 'old-domain.com',
            'source' => 'disposable-email-domains',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->artisan('disposable:import', [
            'source' => 'disposable-email-domains',
            '--clear' => true,
        ])->assertExitCode(0);

        $oldDomainExists = DB::table('disposable_domains')
            ->where('domain', 'old-domain.com')
            ->exists();

        $this->assertFalse($oldDomainExists);
    }

    public function testUnknownSourceListsAvailableSources(): void
    {
        $this->artisan('disposable:import', ['source' => 'nonexistent'])
            ->expectsOutputToContain('Available sources')
            ->assertExitCode(1);
    }
}
