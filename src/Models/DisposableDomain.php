<?php

declare(strict_types=1);

namespace Raul3k\DisposableBlocker\Laravel\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $domain
 * @property string|null $source
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class DisposableDomain extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'domain',
        'source',
    ];

    /**
     * Get the table associated with the model.
     */
    public function getTable(): string
    {
        return config('disposable-blocker.database.table', 'disposable_domains');
    }

    /**
     * Get the database connection for the model.
     */
    public function getConnectionName(): ?string
    {
        return config('disposable-blocker.database.connection');
    }
}
