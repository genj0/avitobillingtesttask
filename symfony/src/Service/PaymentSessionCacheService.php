<?php


namespace App\Service;


use Psr\Cache\CacheItemPoolInterface;

/**
 * Implementing Saving Sessions in the Cache
 * @package App\Service
 */
final class PaymentSessionCacheService implements PaymentSessionServiceInterface
{
    /**
     * @var CacheItemPoolInterface
     */
    private $cache;

    public function __construct(CacheItemPoolInterface $sessionsCache)
    {
        $this->cache = $sessionsCache;
    }

    /**
     * Gets the payment number
     *
     * @param string $sessionId     Session id
     *
     * @return int|null             return null if the session is not found
     * @throws \Psr\Cache\InvalidArgumentException
     */
    public function getPaymentId(string $sessionId): ?int
    {
        $cacheItem = $this->cache->getItem($sessionId);

        if ($cacheItem->isHit()) {
            return $cacheItem->get();
        } else {
            return null;
        }
    }

    /**
     * Creates a session and returns its ID
     *
     * @param int $id   unique identifier of payment
     * @param int $ttl  session lifetime in sec
     *
     * @return string   Session ID in format UUIDv4
     * @throws \Psr\Cache\InvalidArgumentException
     */
    public function createPaymentSession(int $id, int $ttl = self::DEFAULT_TTL): string
    {
        $ttl = $ttl ?? self::DEFAULT_TTL;
        $uuid = uuid_create(UUID_TYPE_RANDOM);
        $cacheItem = $this->cache->getItem($uuid);
        if (!$cacheItem->isHit()) {
            $cacheItem->expiresAfter($ttl);
            $this->cache->save($cacheItem->set($id));
        } else {
            return $this->createPaymentSession($id, $ttl);
        }

        return $uuid;
    }

    /**
     * Deletes a session
     *
     * @param string $sessionId Session id
     *
     * @return bool             return true if deletion is successful, false if an error occurs
     * @throws \Psr\Cache\InvalidArgumentException
     */
    public function deletePaymentSession(string $sessionId): bool
    {
        return $this->cache->deleteItem($sessionId);
    }
}