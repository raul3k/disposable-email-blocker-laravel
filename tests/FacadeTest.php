<?php

declare(strict_types=1);

namespace Raul3k\DisposableBlocker\Laravel\Tests;

use Raul3k\BlockDisposable\Core\CheckResult;
use Raul3k\BlockDisposable\Core\DomainInfo;
use Raul3k\DisposableBlocker\Laravel\Facades\DisposableEmail;

class FacadeTest extends TestCase
{
    public function testIsDisposableReturnsTrueForDisposableDomain(): void
    {
        $result = DisposableEmail::isDisposable('user@mailinator.com');

        $this->assertTrue($result);
    }

    public function testIsDisposableReturnsFalseForValidDomain(): void
    {
        $result = DisposableEmail::isDisposable('user@gmail.com');

        $this->assertFalse($result);
    }

    public function testCheckReturnsCheckResult(): void
    {
        $result = DisposableEmail::check('user@mailinator.com');

        $this->assertInstanceOf(CheckResult::class, $result);
        $this->assertTrue($result->isDisposable());
    }

    public function testCheckSafeDoesNotThrow(): void
    {
        $result = DisposableEmail::checkSafe('invalid-email');

        $this->assertInstanceOf(CheckResult::class, $result);
        $this->assertFalse($result->isDisposable());
    }

    public function testInfoReturnsDomainInfo(): void
    {
        $info = DisposableEmail::info('user@mail.example.co.uk');

        $this->assertInstanceOf(DomainInfo::class, $info);
        $this->assertEquals('example.co.uk', $info->domain());
        $this->assertEquals('mail', $info->subdomain());
    }

    public function testIsDomainDisposable(): void
    {
        $this->assertTrue(DisposableEmail::isDomainDisposable('mailinator.com'));
        $this->assertFalse(DisposableEmail::isDomainDisposable('gmail.com'));
    }

    public function testCheckBatch(): void
    {
        $emails = [
            'user@gmail.com',
            'user@mailinator.com',
            'user@yahoo.com',
        ];

        $results = DisposableEmail::checkBatch($emails);

        $this->assertCount(3, $results);
        $this->assertFalse($results['user@gmail.com']->isDisposable());
        $this->assertTrue($results['user@mailinator.com']->isDisposable());
        $this->assertFalse($results['user@yahoo.com']->isDisposable());
    }

    public function testIsDisposableBatch(): void
    {
        $emails = [
            'user@gmail.com',
            'user@mailinator.com',
        ];

        $results = DisposableEmail::isDisposableBatch($emails);

        $this->assertFalse($results['user@gmail.com']);
        $this->assertTrue($results['user@mailinator.com']);
    }

    public function testNormalize(): void
    {
        $domain = DisposableEmail::normalize('user@mail.example.co.uk');

        $this->assertEquals('example.co.uk', $domain);
    }
}
