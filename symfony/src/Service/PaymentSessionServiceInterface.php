<?php


namespace App\Service;

/**
 * Interface for managing payment sessions
 *
 * @package App\Service
 */
interface PaymentSessionServiceInterface
{
    /**
     * Default session lifetime in sec
     */
    const DEFAULT_TTL = 1800;

    /**
     * Gets the payment number
     *
     * @param string $sessionId     Session id
     *
     * @return int|null             return null if the session is not found
     */
    public function getPaymentId(string $sessionId): ?int;

    /**
     * Creates a session and returns its ID
     *
     * @param int $id   unique identifier of payment
     * @param int $ttl  session lifetime in sec
     *
     * @return string   Session ID in format UUIDv4
     */
    public function createPaymentSession(int $id, int $ttl = self::DEFAULT_TTL): string;

    /**
     * Deletes a session
     *
     * @param string $sessionId Session id
     *
     * @return bool             return true if deletion is successful, false if an error occurs
     */
    public function deletePaymentSession(string $sessionId): bool;

}