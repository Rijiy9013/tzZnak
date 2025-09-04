<?php
declare(strict_types=1);

namespace App\Infrastructure\Dadata\Cache;

use App\Infrastructure\Dadata\DadataClientInterface;
use Psr\SimpleCache\CacheInterface;

final class CachedDadataClient implements DadataClientInterface
{
    private const TTL_SECONDS = 86400; // 24 часа

    public function __construct(
        private readonly DadataClientInterface $inner,
        private readonly CacheInterface        $cache
    )
    {
    }

    public function innExists(string $inn): bool
    {
        $key = 'dadata_inn_' . $inn;

        $cached = $this->cache->get($key);
        if ($cached !== null) {
            return (bool)$cached;
        }

        $exists = $this->inner->innExists($inn);
        $this->cache->set($key, $exists, self::TTL_SECONDS);
        return $exists;
    }
}
