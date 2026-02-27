<?php

declare(strict_types=1);

namespace Raul3k\DisposableBlocker\Laravel;

use Illuminate\Contracts\Cache\Factory as CacheFactory;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\ServiceProvider;
use InvalidArgumentException;
use Raul3k\DisposableBlocker\Core\DisposableEmailChecker;
use Raul3k\DisposableBlocker\Core\DisposableEmailCheckerBuilder;
use Raul3k\DisposableBlocker\Laravel\Cache\LaravelCacheAdapter;
use Raul3k\DisposableBlocker\Laravel\Checkers\DatabaseChecker;
use Raul3k\DisposableBlocker\Core\Sources\SourceRegistry;
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

        $this->app->singleton(SourceRegistry::class);
    }

    /**
     * Bootstrap the service provider.
     */
    public function boot(): void
    {
        $this->loadTranslationsFrom(__DIR__ . '/../lang', 'disposable-blocker');

        $this->publishes([
            __DIR__ . '/../config/disposable-blocker.php' => config_path('disposable-blocker.php'),
        ], 'disposable-blocker-config');

        $this->publishes([
            __DIR__ . '/../database/migrations/' => database_path('migrations'),
        ], 'disposable-blocker-migrations');

        $this->publishes([
            __DIR__ . '/../lang' => $this->app->langPath('vendor/disposable-blocker'),
        ], 'disposable-blocker-lang');

        Validator::extend('not_disposable_email', function (string $attribute, mixed $value): bool {
            if (!is_string($value) || $value === '') {
                return true;
            }

            /** @var DisposableEmailChecker $checker */
            $checker = $this->app->make('disposable-email');

            return !$checker->isDisposableSafe($value);
        }, 'Disposable email addresses are not allowed.');

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
        $useBundledList = $config['use_bundled_list'] ?? true;

        if ($checkerType === 'file' && !$useBundledList) {
            throw new InvalidArgumentException(
                'Checker type "file" requires "use_bundled_list" to be true, or use "database" / "chain" checker instead.'
            );
        }

        match ($checkerType) {
            'database' => $builder->withChecker($this->createDatabaseChecker($config)),
            'chain' => $this->addChainCheckers($builder, $config),
            'file', 'pattern' => null,
            default => throw new InvalidArgumentException(
                sprintf('Invalid checker type "%s". Available: file, database, pattern, chain.', $checkerType)
            ),
        };

        if ($checkerType === 'chain' && empty($config['database']['table']) && !$useBundledList) {
            throw new InvalidArgumentException(
                'Checker type "chain" requires at least one source: configure a database table or enable "use_bundled_list".'
            );
        }

        if ($useBundledList) {
            $builder->withBundledDomains();
        }

        if ($checkerType === 'pattern' || ($config['pattern_detection'] ?? false)) {
            $builder->withPatternDetection();
        }

        $whitelist = $config['whitelist'] ?? [];
        if (!empty($whitelist)) {
            $builder->withWhitelist($whitelist);
        }

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
    private function createDatabaseChecker(array $config): DatabaseChecker
    {
        $dbConfig = $config['database'] ?? [];

        return new DatabaseChecker(
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

        if (!empty($dbConfig['table'])) {
            $builder->withChecker($this->createDatabaseChecker($config));
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
