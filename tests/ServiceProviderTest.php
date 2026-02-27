<?php

declare(strict_types=1);

namespace Raul3k\DisposableBlocker\Laravel\Tests;

use Raul3k\DisposableBlocker\Core\DisposableEmailChecker;

class ServiceProviderTest extends TestCase
{
    public function testCheckerIsBoundAsSingleton(): void
    {
        $instance1 = $this->app->make('disposable-email');
        $instance2 = $this->app->make('disposable-email');

        $this->assertSame($instance1, $instance2);
    }

    public function testCheckerIsResolvableByClass(): void
    {
        $checker = $this->app->make(DisposableEmailChecker::class);

        $this->assertInstanceOf(DisposableEmailChecker::class, $checker);
    }

    public function testDefaultConfigIsFileChecker(): void
    {
        $checker = $this->app->make(DisposableEmailChecker::class);

        $this->assertFalse($checker->isDisposable('user@gmail.com'));
        $this->assertTrue($checker->isDisposable('user@mailinator.com'));
    }

    public function testWhitelistConfigIsApplied(): void
    {
        $this->app['config']->set('disposable-blocker.whitelist', ['mailinator.com']);

        $this->app->forgetInstance('disposable-email');
        $this->app->singleton('disposable-email', function ($app) {
            return (new \ReflectionMethod(
                \Raul3k\DisposableBlocker\Laravel\DisposableBlockerServiceProvider::class,
                'buildChecker'
            ))->invoke($this->app->getProvider(\Raul3k\DisposableBlocker\Laravel\DisposableBlockerServiceProvider::class), $app);
        });

        $checker = $this->app->make('disposable-email');
        $result = $checker->check('user@mailinator.com');

        $this->assertTrue($result->isWhitelisted());
    }

    public function testPatternCheckerTypeEnablesPatternDetection(): void
    {
        $this->app['config']->set('disposable-blocker.checker', 'pattern');
        $this->app['config']->set('disposable-blocker.use_bundled_list', false);

        $this->app->forgetInstance('disposable-email');
        $this->app->singleton('disposable-email', function ($app) {
            return (new \ReflectionMethod(
                \Raul3k\DisposableBlocker\Laravel\DisposableBlockerServiceProvider::class,
                'buildChecker'
            ))->invoke($this->app->getProvider(\Raul3k\DisposableBlocker\Laravel\DisposableBlockerServiceProvider::class), $app);
        });

        $checker = $this->app->make('disposable-email');

        $this->assertTrue($checker->isDisposable('user@tempmail.com'));
    }

    public function testConfigIsPublishable(): void
    {
        $this->artisan('vendor:publish', [
            '--tag' => 'disposable-blocker-config',
            '--no-interaction' => true,
        ])->assertSuccessful();
    }
}
