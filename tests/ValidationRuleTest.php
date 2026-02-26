<?php

declare(strict_types=1);

namespace Raul3k\DisposableBlocker\Laravel\Tests;

use Illuminate\Support\Facades\Validator;
use Raul3k\DisposableBlocker\Laravel\Rules\NotDisposableEmail;

class ValidationRuleTest extends TestCase
{
    public function testValidEmailPassesValidation(): void
    {
        $validator = Validator::make(
            ['email' => 'user@gmail.com'],
            ['email' => ['required', 'email', new NotDisposableEmail()]]
        );

        $this->assertFalse($validator->fails());
    }

    public function testDisposableEmailFailsValidation(): void
    {
        $validator = Validator::make(
            ['email' => 'user@mailinator.com'],
            ['email' => ['required', 'email', new NotDisposableEmail()]]
        );

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('email', $validator->errors()->toArray());
    }

    public function testCustomMessageIsUsed(): void
    {
        $customMessage = 'Temporary emails are not accepted.';

        $validator = Validator::make(
            ['email' => 'user@mailinator.com'],
            ['email' => ['required', 'email', new NotDisposableEmail($customMessage)]]
        );

        $this->assertTrue($validator->fails());
        $this->assertEquals($customMessage, $validator->errors()->first('email'));
    }

    public function testEmptyValuePasses(): void
    {
        $validator = Validator::make(
            ['email' => ''],
            ['email' => [new NotDisposableEmail()]]
        );

        $this->assertFalse($validator->fails());
    }

    public function testNullValuePasses(): void
    {
        $validator = Validator::make(
            ['email' => null],
            ['email' => ['nullable', new NotDisposableEmail()]]
        );

        $this->assertFalse($validator->fails());
    }

    public function testNonStringValuePasses(): void
    {
        $validator = Validator::make(
            ['email' => 123],
            ['email' => [new NotDisposableEmail()]]
        );

        // Non-string values pass through (should be caught by 'email' rule)
        $this->assertFalse($validator->fails());
    }

    public function testAnotherDisposableDomain(): void
    {
        $validator = Validator::make(
            ['email' => 'test@guerrillamail.com'],
            ['email' => ['required', 'email', new NotDisposableEmail()]]
        );

        $this->assertTrue($validator->fails());
    }
}
