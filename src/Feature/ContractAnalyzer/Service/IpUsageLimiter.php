<?php

declare(strict_types=1);

namespace App\Feature\ContractAnalyzer\Service;

use Psr\Cache\CacheItemPoolInterface;

final class IpUsageLimiter
{
    private const string KEY_PREFIX = 'contract_analysis_ip_';
    private const int LIMIT = 5;
    private const int TTL = 86400;

    public function __construct(
        private readonly CacheItemPoolInterface $cache
    ) {
    }

    public function isAllowed(string $ip): bool
    {
        if ($this->isPaid($ip)) {
            return true;
        }

        $item = $this->cache->getItem($this->getCacheKey($ip));

        if (!$item->isHit()) {
            return true;
        }

        return (int) $item->get() < self::LIMIT;
    }

    public function increment(string $ip): void
    {
        $item = $this->cache->getItem($this->getCacheKey($ip));

        if (!$item->isHit()) {
            $item->set(1);
            $item->expiresAfter(self::TTL);
        } else {
            $count = (int) $item->get();
            $item->set($count + 1);
            // TTL is only set on first call as per requirements
        }

        $this->cache->save($item);
    }

    public function isPaid(string $ip): bool
    {
        $item = $this->cache->getItem($this->getPaidKey($ip));

        return $item->isHit() && $item->get() === true;
    }

    public function markAsPaid(string $ip): void
    {
        $item = $this->cache->getItem($this->getPaidKey($ip));
        $item->set(true);
        $item->expiresAfter(self::TTL);

        $this->cache->save($item);
    }


    private function getPaidKey(string $ip): string
    {
        return self::KEY_PREFIX . 'paid_' . md5($ip);
    }

    private function getCacheKey(string $ip): string
    {
        return self::KEY_PREFIX . md5($ip);
    }

    public function getCount(string $ip): int
    {
        $item = $this->cache->getItem($this->getCacheKey($ip));

        if (!$item->isHit()) {
            return 0;
        }

        return (int) $item->get();
    }
}
