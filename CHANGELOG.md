# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

## [1.0.0] - 2025-01-01

### Added

- `DisposableBlockerServiceProvider` with singleton registration and lazy initialization
- `DisposableEmail` facade for static API access
- `NotDisposableEmail` validation rule for Laravel form requests
- `EloquentChecker` for database-backed domain lookups
- `LaravelCacheAdapter` bridging Laravel cache to core `CacheInterface`
- `DisposableDomain` Eloquent model with configurable table and connection
- Migration for `disposable_domains` table
- `disposable:update` Artisan command to update domains from all sources
- `disposable:import` Artisan command to import from a specific source
- `disposable:list-sources` Artisan command to list available sources
- Configuration file with support for file, database, pattern, and chain checker types
- Whitelist and cache configuration
- Laravel 10, 11, and 12 support
