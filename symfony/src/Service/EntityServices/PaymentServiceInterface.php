<?php

namespace App\Service\EntityServices;

use App\Entity\Payment;

/**
 * Interface describing functions for working with payments
 *
 * @package App\Service\EntityServices
 */
interface PaymentServiceInterface
{
    const ALLOWED_OPTIONAL_RESP_FIELDS = ['notification', 'createdAt'];
    const DEFAULT_RESP_FIELDS = ['purpose', 'amount', 'orderId', 'status'];
    const DEFAULT_ORDER = '-id';
    const ORDERLY_FIELDS = ['purpose', 'amount', 'orderId', 'createdAt'];

    /**
     * Return payment data by order number
     *
     * @param string $orderId           Order id in the store system
     * @param string $optionalFields    String containing the names of the properties to be added to the return
     *
     * @return array|null               Returns a normalized entity, null if an entity with such orderId is not found
     */
    public function getPaymentData(string $orderId, string $optionalFields): ?array;

    /**
     * Getting the count of Payments
     *
     * @param array|null $criteria
     * @return int                   The count of the entities that match the given criteria.
     */
    public function getPaymentsCount(array $criteria = null): int;

    /**
     * Getting an array with data from several payments
     *
     * @param int|null $page            Entities page number.
     *                                  Parameter will have an effect only when used with not null $resOnPage
     * @param int|null $resOnPage       Number of entities on return.
     *                                  Parameter will have an effect only when used with not null $page
     * @param string $optionalFields    String containing the names of the properties to be added to the return
     * @param string $orderBy           String containing properties names with their sorting methods Ex: asc_id
     *
     * @return array                    Array of normalized entities
     */
    public function getPaymentsPageData(
        int $page,
        int $resOnPage = null,
        string $optionalFields = '',
        string $orderBy = self::DEFAULT_ORDER
    ): array;

    /**
     * Payment getting by payment session id
     *
     * @param string $sessionId  payment session id
     *
     * @return Payment|null     Returns an {@link Payment} object if a session and payment are found, null otherwise
     */
    public function getPaymentBySessionId(string $sessionId): ?Payment;

    /**
     * Payment getting by payment orderId
     *
     * @param string $orderId   Order id in the store system
     *
     * @return Payment|null     Returns an {@link Payment} object if a session and payment are found, null otherwise
     */
    public function getPaymentByOrderId(string $orderId): ?Payment;

    /**
     * Http notification sending
     *
     * @param Payment $payment      {@link Payment} object for which a notification should be sent
     * @param string|null $fields   String containing additional properties that should be included in the notification
     */
    public function sendNotification(Payment $payment, string $fields = null);

    /**
     * Receive data on payments for a given period
     *
     * @param \DateTimeInterface $startsOn  Beginning of period
     * @param \DateTimeInterface $endsOn    End of period
     * @param string $optFields             String containing the names of the properties to be added to the return
     * @param int|null $page                Results page number.
     *                                      Parameter will have an effect only when used with not null $resOnPage
     * @param int|null $resOnPage           Number of Payments on return.
     *                                      Parameter will have an effect only when used with not null $page
     * @return array                        Array of normalized {@link Payment}s
     */
    public function getPaymentsDataFromPeriod(
        \DateTimeInterface $startsOn,
        \DateTimeInterface $endsOn,
        string $optFields = '',
        int $page = null,
        int $resOnPage = null
    ): array;

    /**
     *  Getting the count of payments for a given period
     *
     * @param \DateTimeInterface $startsOn  Beginning of period
     * @param \DateTimeInterface $endsOn    End of period
     *
     * @return int                          Count of payments
     */
    public function getPaymentsCountFromPeriod(\DateTimeInterface $startsOn, \DateTimeInterface $endsOn): int;

    /**
     * Creates a payment session
     *
     * @param Payment $payment  {@link Payment} object for which to create a payment session
     *
     * @return string|null      Returns a string in the UUIDv4 format, null if a session creation error occurred
     */
    public function createPaymentSession(Payment $payment): ?string;
}
