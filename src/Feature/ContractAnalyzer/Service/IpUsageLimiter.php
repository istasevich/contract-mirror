<?php

declare(strict_types=1);

namespace App\Feature\ContractAnalyzer\Service;

use Psr\Cache\CacheItemPoolInterface;

final class IpUsageLimiter
{
    private const string KEY_PREFIX = 'contract_analysis_';
    public const int LIMIT = 3;
    private const int TTL = 86400;

    public function __construct(
        private readonly CacheItemPoolInterface $cache
    ) {
        // Nothing
    }

    public function isAllowed(string $ip, string $visitorId, ?string $userAgent = null): bool
    {
        if ($this->isPaid($ip, $visitorId, $userAgent)) {
            return true;
        }

        $item = $this->cache->getItem($this->getCacheKey($ip, $visitorId, $userAgent));

        if (!$item->isHit()) {
            return true;
        }

        return (int)$item->get() < self::LIMIT;
    }

    public function reserveUsage(string $ip, string $visitorId, ?string $userAgent = null): bool
    {
        if ($this->isPaid($ip, $visitorId, $userAgent)) {
            return true;
        }

        $item = $this->cache->getItem($this->getCacheKey($ip, $visitorId, $userAgent));
        $count = $item->isHit() ? (int) $item->get() : 0;

        if ($count >= self::LIMIT) {
            return false;
        }

        $item->set($count + 1);
        $item->expiresAfter(self::TTL);
        $this->cache->save($item);

        return true;
    }

    public function increment(string $ip, string $visitorId, ?string $userAgent = null): void
    {
        $item = $this->cache->getItem($this->getCacheKey($ip, $visitorId, $userAgent));

        if (!$item->isHit()) {
            $item->set(1);
            $item->expiresAfter(self::TTL);
        } else {
            $count = (int)$item->get();
            $item->set($count + 1);
        }

        $this->cache->save($item);
    }

    public function getCount(string $ip, string $visitorId, ?string $userAgent = null): int
    {
        $item = $this->cache->getItem($this->getCacheKey($ip, $visitorId, $userAgent));

        if (!$item->isHit()) {
            return 0;
        }

        return (int)$item->get();
    }

    public function isPaid(string $ip, string $visitorId, ?string $userAgent = null): bool
    {
        $item = $this->cache->getItem($this->getPaidKey($ip, $visitorId, $userAgent));

        return $item->isHit() && $item->get() === true;
    }

    public function markAsPaid(string $ip, string $visitorId, ?string $userAgent = null): void
    {
        $item = $this->cache->getItem($this->getPaidKey($ip, $visitorId, $userAgent));
        $item->set(true);

        $item->expiresAfter(8 * 86400); // 8 дней отчет доступен

        $this->cache->save($item);
    }

    private function getCacheKey(string $ip, string $visitorId, ?string $userAgent): string
    {
        return self::KEY_PREFIX . 'usage_' . hash('sha256', $this->buildIdentity($ip, $visitorId, $userAgent));
    }

    private function getPaidKey(string $ip, string $visitorId, ?string $userAgent): string
    {
        return self::KEY_PREFIX . 'paid_' . hash('sha256', $this->buildIdentity($ip, $visitorId, $userAgent));
    }

    private function buildIdentity(string $ip, string $visitorId, ?string $userAgent): string
    {
        $visitorId = preg_match('/^[A-Za-z0-9._:-]{8,128}$/', $visitorId) === 1 ? $visitorId : 'anonymous';

        return $ip . ':' . $visitorId . ':' . hash('sha256', (string) $userAgent);
    }
}
