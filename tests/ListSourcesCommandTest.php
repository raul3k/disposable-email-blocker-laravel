<?php

declare(strict_types=1);

namespace Raul3k\DisposableBlocker\Laravel\Tests;

class ListSourcesCommandTest extends TestCase
{
    public function testCommandExitsWithSuccess(): void
    {
        $this->artisan('disposable:list-sources')
            ->assertExitCode(0);
    }

    public function testCommandOutputContainsSourceNames(): void
    {
        $this->artisan('disposable:list-sources')
            ->expectsOutputToContain('disposable-email-domains')
            ->assertExitCode(0);
    }

    public function testCommandOutputContainsUsageInstructions(): void
    {
        $this->artisan('disposable:list-sources')
            ->expectsOutputToContain('disposable:import')
            ->expectsOutputToContain('disposable:update')
            ->assertExitCode(0);
    }
}
