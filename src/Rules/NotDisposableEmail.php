<?php

declare(strict_types=1);

namespace Raul3k\DisposableBlocker\Laravel\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Raul3k\DisposableBlocker\Core\DisposableEmailChecker;

/**
 * Validation rule that rejects disposable email addresses.
 *
 * @example
 * $request->validate([
 *     'email' => ['required', 'email', new NotDisposableEmail()],
 * ]);
 *
 * @example
 * // With custom message
 * 'email' => [new NotDisposableEmail('Temporary emails are not allowed')],
 */
class NotDisposableEmail implements ValidationRule
{
    public function __construct(
        private readonly ?string $message = null
    ) {}

    /**
     * Run the validation rule.
     *
     * @param \Closure(string, ?string=): \Illuminate\Translation\PotentiallyTranslatedString $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (!is_string($value) || $value === '') {
            return;
        }

        /** @var DisposableEmailChecker $checker */
        $checker = app('disposable-email');

        if ($checker->isDisposableSafe($value)) {
            $fail($this->message ?? 'disposable-blocker::validation.not_disposable_email');
        }
    }
}
