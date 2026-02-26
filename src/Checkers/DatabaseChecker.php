<?php

declare(strict_types=1);

namespace Raul3k\DisposableBlocker\Laravel\Checkers;

use Countable;
use Illuminate\Support\Facades\DB;
use Raul3k\DisposableBlocker\Core\Checkers\CheckerInterface;

/**
 * Database checker for disposable domains.
 *
 * Uses Laravel's Query Builder to check if a domain exists
 * in the disposable domains table.
 */
class DatabaseChecker implements CheckerInterface, Countable
{
    public function __construct(
        private readonly string $table = 'disposable_domains',
        private readonly ?string $connection = null
    ) {}

    /**
     * Check if the given domain is in the disposable domains table.
     */
    public function isDomainDisposable(string $normalizedDomain): bool
    {
        $query = DB::connection($this->connection)
            ->table($this->table)
            ->where('domain', $normalizedDomain);

        return $query->exists();
    }

    /**
     * Get all domains from the database.
     *
     * @return array<string>
     */
    public function getAllDomains(): array
    {
        return DB::connection($this->connection)
            ->table($this->table)
            ->pluck('domain')
            ->toArray();
    }

    /**
     * Get domain count.
     */
    public function count(): int
    {
        return DB::connection($this->connection)
            ->table($this->table)
            ->count();
    }
}
