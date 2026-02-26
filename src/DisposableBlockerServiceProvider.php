<?php

declare(strict_types=1);

namespace Raul3k\DisposableBlocker\Laravel;

use Illuminate\Contracts\Cache\Factory as CacheFactory;
use Illuminate\Support\ServiceProvider;
use Raul3k\BlockDisposable\Core\DisposableEmailChecker;
use Raul3k\BlockDisposable\Core\DisposableEmailCheckerBuilder;
use Raul3k\DisposableBlocker\Laravel\Cache\LaravelCacheAdapter;
use Raul3k\DisposableBlocker\Laravel\Checkers\EloquentChecker;
use Raul3k\DisposableBlocker\Laravel\Console\ImportDomainsCommand;
use Raul3k\DisposableBlocker\Laravel\Console\ListSourcesCommand;
use Raul3k\DisposableBlocker\Laravel\Console\UpdateDomainsCommand;

class DisposableBlockerServiceProvider extends ServiceProvider
{
    /**
     * Register the service provider.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__ . '/../config/disposable-blocker.php',
            'disposable-blocker'
        );

        $this->app->singleton('disposable-email', function ($app) {
            return $this->buildChecker($app);
        });

        $this->app->alias('disposable-email', DisposableEmailChecker::class);
    }

    /**
     * Bootstrap the service provider.
     */
    public function boot(): void
    {
        $this->publishes([
            __DIR__ . '/../config/disposable-blocker.php' => config_path('disposable-blocker.php'),
        ], 'disposable-blocker-config');

        $this->publishes([
            __DIR__ . '/../database/migrations/' => database_path('migrations'),
        ], 'disposable-blocker-migrations');

        if ($this->app->runningInConsole()) {
            $this->commands([
                UpdateDomainsCommand::class,
                ImportDomainsCommand::class,
                ListSourcesCommand::class,
            ]);
        }
    }

    /**
     * Build the disposable email checker based on configuration.
     */
    private function buildChecker(mixed $app): DisposableEmailChecker
    {
        /** @var array<string, mixed> $config */
        $config = $app['config']['disposable-blocker'];
        $builder = new DisposableEmailCheckerBuilder();

        $checkerType = $config['checker'] ?? 'file';

        // Add checkers based on configuration
        match ($checkerType) {
            'database' => $builder->withChecker($this->createEloquentChecker($config)),
            'pattern' => null, // Pattern detection added below if enabled
            'chain' => $this->addChainCheckers($builder, $config),
            default => null, // File checker added via bundled list
        };

        // Add bundled list if enabled
        if ($config['use_bundled_list'] ?? true) {
            $builder->withBundledDomains();
        }

        // Add pattern detection if enabled
        if ($config['pattern_detection'] ?? false) {
            $builder->withPatternDetection();
        }

        // Add whitelist
        $whitelist = $config['whitelist'] ?? [];
        if (!empty($whitelist)) {
            $builder->withWhitelist($whitelist);
        }

        // Add cache if enabled
        $cacheConfig = $config['cache'] ?? [];
        if ($cacheConfig['enabled'] ?? true) {
            $cache = $this->createCacheAdapter($app, $cacheConfig);
            $builder->withCache($cache, $cacheConfig['ttl'] ?? 3600);
        }

        return $builder->build();
    }

    /**
     * Create the Eloquent checker.
     *
     * @param array<string, mixed> $config
     */
    private function createEloquentChecker(array $config): EloquentChecker
    {
        $dbConfig = $config['database'] ?? [];

        return new EloquentChecker(
            table: $dbConfig['table'] ?? 'disposable_domains',
            connection: $dbConfig['connection'] ?? null
        );
    }

    /**
     * Add chain checkers to the builder.
     *
     * @param array<string, mixed> $config
     */
    private function addChainCheckers(DisposableEmailCheckerBuilder $builder, array $config): void
    {
        $dbConfig = $config['database'] ?? [];

        // When using chain mode, add both database and file checkers
        if (!empty($dbConfig['table'])) {
            $builder->withChecker($this->createEloquentChecker($config));
        }
    }

    /**
     * Create the Laravel cache adapter.
     *
     * @param array<string, mixed> $cacheConfig
     */
    private function createCacheAdapter(mixed $app, array $cacheConfig): LaravelCacheAdapter
    {
        /** @var CacheFactory $cacheFactory */
        $cacheFactory = $app['cache'];

        $store = $cacheConfig['store'] ?? 'default';
        $repository = $store === 'default'
            ? $cacheFactory->store()
            : $cacheFactory->store($store);

        $prefix = $cacheConfig['prefix'] ?? 'disposable_email:';

        return new LaravelCacheAdapter($repository, $prefix);
    }
}
