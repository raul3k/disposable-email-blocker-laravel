<?php

declare(strict_types=1);

namespace Raul3k\DisposableBlocker\Laravel\Tests;

use Orchestra\Testbench\TestCase as OrchestraTestCase;
use Raul3k\DisposableBlocker\Laravel\DisposableBlockerServiceProvider;

abstract class TestCase extends OrchestraTestCase
{
    /**
     * Get package providers.
     *
     * @param \Illuminate\Foundation\Application $app
     * @return array<int, class-string>
     */
    protected function getPackageProviders($app): array
    {
        return [
            DisposableBlockerServiceProvider::class,
        ];
    }

    /**
     * Get package aliases.
     *
     * @param \Illuminate\Foundation\Application $app
     * @return array<string, class-string>
     */
    protected function getPackageAliases($app): array
    {
        return [
            'DisposableEmail' => \Raul3k\DisposableBlocker\Laravel\Facades\DisposableEmail::class,
        ];
    }

    /**
     * Define environment setup.
     *
     * @param \Illuminate\Foundation\Application $app
     */
    protected function defineEnvironment($app): void
    {
        $app['config']->set('disposable-blocker.checker', 'file');
        $app['config']->set('disposable-blocker.use_bundled_list', true);
        $app['config']->set('disposable-blocker.cache.enabled', false);
    }
}
