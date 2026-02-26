<?php

declare(strict_types=1);

namespace Raul3k\DisposableBlocker\Laravel\Facades;

use Illuminate\Support\Facades\Facade;
use Raul3k\BlockDisposable\Core\CheckResult;
use Raul3k\BlockDisposable\Core\DomainInfo;

/**
 * @method static bool isDisposable(string $email)
 * @method static bool isDisposableSafe(string $email)
 * @method static bool isDomainDisposable(string $domain)
 * @method static bool isDomainDisposableSafe(string $domain)
 * @method static CheckResult check(string $email)
 * @method static CheckResult checkSafe(string $email)
 * @method static CheckResult checkDomain(string $domain)
 * @method static array<string, CheckResult> checkBatch(array $emails)
 * @method static array<string, bool> isDisposableBatch(array $emails)
 * @method static string normalize(string $email)
 *
 * @see \Raul3k\BlockDisposable\Core\DisposableEmailChecker
 */
class DisposableEmail extends Facade
{
    /**
     * Get the registered name of the component.
     */
    protected static function getFacadeAccessor(): string
    {
        return 'disposable-email';
    }

    /**
     * Get domain information for an email or domain.
     */
    public static function info(string $input): DomainInfo
    {
        return DomainInfo::parse($input);
    }
}
