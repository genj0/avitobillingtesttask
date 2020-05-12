<?php


namespace App\Service\EntityServices;


use App\Entity\Payment;
use App\Repository\PaymentRepositoryService;
use App\Service\PaymentSessionServiceInterface;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

/**
 * Class PaymentEntityService
 * @package App\Service\EntityServices
 */
final class PaymentEntityService extends AbstractEntityService implements PaymentServiceInterface
{
    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var PaymentSessionServiceInterface
     */
    private $paymentSessionService;

    /**
     * @var PaymentRepositoryService
     */
    protected $objectRepository;

    public function __construct(
        EntityManagerInterface $entityManager,
        SerializerInterface $serializer,
        LoggerInterface $logger,
        PaymentRepositoryService $repositoryService,
        PaymentSessionServiceInterface $paymentSessionService
    ) {
        $this->paymentSessionService = $paymentSessionService;
        $this->logger = $logger;
        parent::__construct($entityManager, $repositoryService, $serializer);
    }

    /**
     * {@inheritDoc}
     */
    public function getPaymentData(string $orderId, string $optionalFields): ?array
    {
        $payment = $this->objectRepository->findOneBy(['orderId' => $orderId]);

        return (is_null($payment))
            ? null
            : $this->normalizeEntity(
                $payment,
                $optionalFields,
                self::ALLOWED_OPTIONAL_RESP_FIELDS,
                self::DEFAULT_RESP_FIELDS
            );
    }

    /**
     * {@inheritDoc}
     */
    public function getPaymentsPageData(
        int $page,
        int $resOnPage = null,
        string $optionalFields = '',
        string $orderBy = self::DEFAULT_ORDER
    ): array {
        return $this->getEntitiesPageData(
            $page,
            $resOnPage,
            $optionalFields,
            self::ALLOWED_OPTIONAL_RESP_FIELDS,
            self::DEFAULT_RESP_FIELDS,
            $orderBy,
            self::ORDERLY_FIELDS
        );
    }

    /**
     * {@inheritDoc}
     */
    public function getPaymentsDataFromPeriod(
        \DateTimeInterface $startsOn,
        \DateTimeInterface $endsOn,
        string $optFields = '',
        int $page = null,
        int $resOnPage = null
    ): array {
        $itemsData = [];
        $offset = (is_null($page) or is_null($resOnPage)) ? null : (($page - 1) * $resOnPage);

        $payments = $this->objectRepository->findByPeriod($startsOn, $endsOn, ['createdAt' => 'desc'], $resOnPage, $offset);

        foreach ($payments as $payment) {
            $itemsData[] = $this->normalizeEntity(
                $payment,
                $optFields,
                self::ALLOWED_OPTIONAL_RESP_FIELDS,
                self::DEFAULT_RESP_FIELDS
            );
        }

        return $itemsData;
    }

    /**
     * {@inheritDoc}
     */
    public function getPaymentsCountFromPeriod(\DateTimeInterface $startsOn, \DateTimeInterface $endsOn): int
    {
        return $this->objectRepository->countByPeriod($startsOn, $endsOn);
    }

    /**
     * {@inheritDoc}
     */
    public function getPaymentsCount(array $criteria = null): int
    {
        return $this->count($criteria);
    }

    /**
     * {@inheritDoc}
     */
    public function getPaymentByOrderId(string $orderId): ?Payment
    {
        return $this->objectRepository->findOneBy(['orderId' => $orderId]);
    }

    /**
     * {@inheritDoc}
     */
    public function sendNotification(Payment $payment, string $fields = null)
    {
        $fields = $fields ?? '';
        if (!is_null($payment->getNotification())) {
            $client = HttpClient::create();
            $data = $this->normalizeEntity(
                $payment,
                $fields,
                self::ALLOWED_OPTIONAL_RESP_FIELDS,
                self::DEFAULT_RESP_FIELDS
            );
            try {
                //Responses are always asynchronous, so that the call to the method returns immediately
                // instead of waiting to receive the response
                $client->request('GET', $payment->getNotification(), ['query' => $data]);
            } catch (TransportExceptionInterface $e) {
                $this->logger->warning('Error sending notification: '.$e->getMessage());
            }
        }
    }

    /**
     * {@inheritDoc}
     */
    public function createPaymentSession(Payment $payment): ?string
    {
        return $this->paymentSessionService->createPaymentSession($payment->getId());
    }

    /**
     * {@inheritDoc}
     */
    public function getPaymentBySessionId(string $sessionId): ?Payment
    {
        $paymentId = $this->paymentSessionService->getPaymentId($sessionId);

        return (is_null($paymentId)) ? null : $this->objectRepository->find($paymentId);
    }
}