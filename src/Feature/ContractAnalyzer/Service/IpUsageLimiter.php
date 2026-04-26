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

    public function isAllowed(string $ip, string $visitorId): bool
    {
        if ($this->isPaid($ip, $visitorId)) {
            return true;
        }

        $item = $this->cache->getItem($this->getCacheKey($ip, $visitorId));

        if (!$item->isHit()) {
            return true;
        }

        return (int)$item->get() < self::LIMIT;
    }

    public function increment(string $ip, string $visitorId): void
    {
        $item = $this->cache->getItem($this->getCacheKey($ip, $visitorId));

        if (!$item->isHit()) {
            $item->set(1);
            $item->expiresAfter(self::TTL);
        } else {
            $count = (int)$item->get();
            $item->set($count + 1);
        }

        $this->cache->save($item);
    }

    public function getCount(string $ip, string $visitorId): int
    {
        $item = $this->cache->getItem($this->getCacheKey($ip, $visitorId));

        if (!$item->isHit()) {
            return 0;
        }

        return (int)$item->get();
    }

    public function isPaid(string $ip, string $visitorId): bool
    {
        $item = $this->cache->getItem($this->getPaidKey($ip, $visitorId));

        return $item->isHit() && $item->get() === true;
    }

    public function markAsPaid(string $ip, string $visitorId): void
    {
        $item = $this->cache->getItem($this->getPaidKey($ip, $visitorId));
        $item->set(true);

        $item->expiresAfter(8 * 86400); // 8 дней отчет доступен

        $this->cache->save($item);
    }

    private function getCacheKey(string $ip, string $visitorId): string
    {
        return self::KEY_PREFIX . 'usage_' . md5($ip . ':' . $visitorId);
    }

    private function getPaidKey(string $ip, string $visitorId): string
    {
        return self::KEY_PREFIX . 'paid_' . md5($ip . ':' . $visitorId);
    }
}
