<?php

declare(strict_types=1);

namespace App\Tests\Feature\ContractAnalyzer;

use App\Feature\ContractAnalyzer\Service\IpUsageLimiter;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Cache\Adapter\ArrayAdapter;

final class IpUsageLimiterTest extends TestCase
{
    public function testReservesOnlyConfiguredFreeLimit(): void
    {
        $limiter = new IpUsageLimiter(new ArrayAdapter());

        self::assertTrue($limiter->reserveUsage('127.0.0.1', 'visitor-12345678', 'ua'));
        self::assertTrue($limiter->reserveUsage('127.0.0.1', 'visitor-12345678', 'ua'));
        self::assertTrue($limiter->reserveUsage('127.0.0.1', 'visitor-12345678', 'ua'));
        self::assertFalse($limiter->reserveUsage('127.0.0.1', 'visitor-12345678', 'ua'));
        self::assertSame(IpUsageLimiter::LIMIT, $limiter->getCount('127.0.0.1', 'visitor-12345678', 'ua'));
    }

    public function testVisitorIdAloneDoesNotDefineIdentity(): void
    {
        $limiter = new IpUsageLimiter(new ArrayAdapter());

        self::assertTrue($limiter->reserveUsage('127.0.0.1', 'same-visitor-123', 'ua-a'));
        self::assertSame(0, $limiter->getCount('127.0.0.1', 'same-visitor-123', 'ua-b'));
    }

    public function testPaidIdentityBypassesFreeLimit(): void
    {
        $limiter = new IpUsageLimiter(new ArrayAdapter());
        $limiter->markAsPaid('127.0.0.1', 'visitor-12345678', 'ua');

        for ($i = 0; $i < 10; $i++) {
            self::assertTrue($limiter->reserveUsage('127.0.0.1', 'visitor-12345678', 'ua'));
        }
    }
}
