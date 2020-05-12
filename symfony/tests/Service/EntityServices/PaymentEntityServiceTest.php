<?php

namespace App\Tests\Service\EntityServices;

use App\DataFixtures\PaymentFixtures;
use App\Entity\Payment;
use App\Service\EntityServices\PaymentEntityService;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class PaymentEntityServiceTest extends WebTestCase
{
    /**
     * @var \Doctrine\ORM\EntityManager
     */
    private $entityManager;

    public function setUp()
    {
        $kernel = self::bootKernel();
        $this->entityManager = $kernel->getContainer()
            ->get('doctrine')
            ->getManager();
    }

    protected function tearDown(): void
    {
        // doing this is recommended to avoid memory leaks
        $this->entityManager->close();
        $this->entityManager = null;
        parent::tearDown();
    }

    /**
     * @dataProvider getPeriodData
     * @param string $startsOn
     * @param string $endsOn
     * @param int $count
     */
    public function testGetPaymentsDataFromPeriod(string $startsOn, string $endsOn, int $count)
    {
        $startsOn = new \DateTime($startsOn);
        $endsOn = new \DateTime($endsOn);
        $container = self::$container->get(PaymentEntityService::class);
        $fixture = new PaymentFixtures();
        $payments = $fixture->load($this->entityManager);


        $paymentsData = $container->getPaymentsDataFromPeriod($startsOn, $endsOn, 'createdAt');
        $paymentsCount = $container->getPaymentsCountFromPeriod($startsOn, $endsOn);
        $this->assertCount($count, $paymentsData);
        $this->assertEquals($count, $paymentsCount);

        foreach ($paymentsData as $paymentData) {
            $realPayment = $payments[$paymentData['orderId']];
            $this->assertEquals($realPayment->getCreatedAt(), new \DateTime($paymentData['createdAt']));
            $this->assertEquals($realPayment->getAmount(), $paymentData['amount']);
            $this->assertEquals($realPayment->getPurpose(), $paymentData['purpose']);
            $this->assertTrue(($realPayment->getCreatedAt() > $startsOn) and ($realPayment->getCreatedAt() < $endsOn));
        }
    }

    /**
     * @dataProvider getPeriodDataPage
     * @param string $startsOn
     * @param string $endsOn
     * @param int $page
     * @param int $resOnPage
     */
    public function testGetPaymentsDataFromPeriodPagination(string $startsOn, string $endsOn, int $page, int $resOnPage)
    {
        $startsOn = new \DateTime($startsOn);
        $endsOn = new \DateTime($endsOn);
        $container = self::$container->get(PaymentEntityService::class);
        $fixture = new PaymentFixtures();
        $payments = $fixture->load($this->entityManager);

        $paymentsPage = $container->getPaymentsDataFromPeriod($startsOn, $endsOn, 'createdAt', $page, $resOnPage);
        $paymentsAll = $container->getPaymentsDataFromPeriod($startsOn, $endsOn);

        $offset = (($page - 1) * $resOnPage);
        $end = min(count($paymentsAll) - 1, $offset + $resOnPage - 1);

        $j = 0;
        for ($i = $offset; $i <= $end; $i++) {
            $paymentExpected = $paymentsAll[$i];
            $paymentActual = $paymentsPage[$j];
            $this->assertEquals($paymentExpected['amount'], $paymentActual['amount']);
            $this->assertEquals($paymentExpected['orderId'], $paymentActual['orderId']);
            $j++;
        }
    }

    public function testGetPaymentsPageData()
    {
        $container = self::$container->get(PaymentEntityService::class);
        $fixture = new PaymentFixtures();
        $payments = $fixture->load($this->entityManager);

        $paymentsData = $container->getPaymentsPageData(2, 3, 'createdAt', 'asc_orderId');

        $this->assertCount(3, $paymentsData);
        $i = 11;
        foreach ($paymentsData as $paymentData) {
            $this->assertEquals($payments['testOrderId'.$i]->getOrderId(), $paymentData['orderId']);
            $this->assertEquals($payments['testOrderId'.$i]->getAmount(), $paymentData['amount']);
            $this->assertEquals($payments['testOrderId'.$i]->getPurpose(), $paymentData['purpose']);
            $i++;
        }
    }

    public function testGetPaymentsCount()
    {
        $container = self::$container->get(PaymentEntityService::class);
        $fixture = new PaymentFixtures();
        $payments = $fixture->load($this->entityManager);
        $this->assertCount($container->getPaymentsCount(), $payments);
    }

    public function testValidPaymentSession()
    {
        $payment = new Payment();
        $payment->setAmount(10000.87);
        $payment->setPurpose('ТестовыйПлатеж 1');
        $payment->setOrderId('testOrderId9999');
        $this->entityManager->persist($payment);
        $this->entityManager->flush();

        $container = self::$container->get(PaymentEntityService::class);

        $sessionId = $container->createPaymentSession($payment);

        $sessionIdPayment = $container->getPaymentBySessionId($sessionId);
        $orderIdPayment = $container->getPaymentByOrderId('testOrderId9999');

        $this->assertEquals($payment, $sessionIdPayment);
        $this->assertEquals($payment, $orderIdPayment);

        $this->entityManager->remove($payment);
        $this->entityManager->flush();
        $this->assertEquals(null, $container->getPaymentBySessionId($sessionId));
        $this->assertEquals(null, $container->getPaymentByOrderId('testOrderId9999'));
    }

    public function testInvalidPaymentSession()
    {
        $container = self::$container->get(PaymentEntityService::class);
        $this->assertEquals(null, $container->getPaymentBySessionId('e99df522-bfe0-47c0-a60b-2e9401a86d43'));
    }

    /**
     * @dataProvider getPaymentData
     * @param $amount
     * @param $purpose
     * @param $orderId
     * @param $notification
     * @param $status
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function testGetPaymentData($amount, $purpose, $orderId, $notification, $status)
    {
        $container = self::$container->get(PaymentEntityService::class);
        $payment = new Payment();
        $payment->setAmount($amount);
        $payment->setPurpose($purpose);
        $payment->setOrderId($orderId);
        if (!is_null($status)) {
            $payment->setStatus($status);
        }
        $payment->setNotification($notification);
        $this->entityManager->persist($payment);
        $this->entityManager->flush();

        $paymentData = $container->getPaymentData($payment->getOrderId(), 'notification');
        $this->assertEquals($payment->getOrderId(), $paymentData['orderId']);
        $this->assertEquals($payment->getPurpose(), $paymentData['purpose']);
        $this->assertEquals($payment->getAmount(), $paymentData['amount']);
        $this->assertEquals($payment->getNotification(), $paymentData['notification']);
        $this->assertEquals($payment->getStatus(), $paymentData['status']);
        $this->assertArrayNotHasKey('createdAt', $paymentData);

        $this->entityManager->remove($payment);
        $this->entityManager->flush();
    }

    /**
     * @dataProvider getOrderCriteriaData
     * @param $initData
     * @param $procData
     * @param $orderlyFields
     */
    public function testGetOrderCriteria($initData, $procData, $orderlyFields)
    {
        $container = self::$container->get(PaymentEntityService::class);

        $this->assertEquals($container->getOrderCriteria($initData, $orderlyFields, ['created' => 'desc']), $procData);
    }

    /**
     * @dataProvider getUrl
     * @param $url
     */
    public function testSendNotification($url)
    {
        $payment = new Payment();
        $payment->setAmount(10000.89);
        $payment->setPurpose('ТестовыйПлатеж 12');
        $payment->setOrderId('testOrderId9990');
        $payment->setNotification($url);

        $container = self::$container->get(PaymentEntityService::class);

        $this->assertNull($container->sendNotification($payment, 'amount, orderId'));
    }

    public function testNonexistentPayment()
    {
        $container = self::$container->get(PaymentEntityService::class);
        $this->assertNull($container->getPaymentData('nonexistentId', 'notification'));
    }

    public function testGetEntityData()
    {
        $payment = new Payment();
        $payment->setAmount(10000.87);
        $payment->setPurpose('ТестовыйПлатеж 1');
        $payment->setOrderId('testOrderId92149');
        $this->entityManager->persist($payment);
        $this->entityManager->flush();
        $id = $payment->getId();
        $container = self::$container->get(PaymentEntityService::class);
        $data = $container->getEntityData($payment->getId(), 'notification', [], ['amount', 'orderId']);

        $this->assertCount(2, $data);
        $this->assertEquals($payment->getAmount(), $data['amount']);
        $this->assertEquals($payment->getOrderId(), $data['orderId']);

        $this->entityManager->remove($payment);
        $this->entityManager->flush();

        $data = $container->getEntityData($id);
        $this->assertNull($data);
    }

    /*
     *
     * Data Providers
     *
     */

    public function getPeriodData()
    {
        return [
            ['2020-04-20 12:00:00', '2020-04-29 15:00:00', 10],
            ['2020-04-22 12:00:00', '2020-04-29 15:00:00', 8],
            ['2020-04-20 12:00:00', '2020-04-29 15:00:00', 10],
            ['2020-04-28 12:00:00', '2020-04-29 12:00:00', 1],
            ['2020-04-20 12:00:00', '2020-04-29 12:00:00', 9],
            ['2020-04-22 12:00:00', '2020-04-23 12:00:00', 1],
            ['2020-04-25 12:00:00', '2020-04-29 12:00:00', 4],
        ];
    }

    public function getPeriodDataPage()
    {
        return [
            ['2020-04-20 12:00:00', '2020-04-29 15:00:00', 2, 5],
            ['2020-04-22 12:00:00', '2020-04-29 15:00:00', 1, 5],
            ['2020-04-20 12:00:00', '2020-04-29 15:00:00', 3, 4],
            ['2020-04-25 12:00:00', '2020-04-29 12:00:00', 2, 3],
            ['2020-04-20 12:00:00', '2020-04-29 12:00:00', 4, 2],
        ];
    }

    public function getOrderCriteriaData()
    {
        return [
            [
                'asc_name, dec_price, ASC(id), ASC_created',
                [
                    'name' => 'asc',
                    'id' => 'asc',
                    'created' => 'asc',
                ],
                ['name', 'id', 'created', 'price'],
            ],
            [
                '',
                ['created' => 'desc'],
                [],
            ],
            [
                'asc_name, desc_price, ASC(id), ASC_created',
                ['created' => 'desc'],
                [],
            ],
            [
                'asc_name, desc_price, ASC(id), ASC_created',
                [
                    'name' => 'asc',
                    'id' => 'asc',
                    'created' => 'asc',
                ],
                ['name', 'id', 'created'],
            ],
            [
                'asc_name, desc_price, ASC(id), ASC_created',
                [
                    'name' => 'asc',
                    'price' => 'desc',
                    'id' => 'asc',
                    'created' => 'asc',
                ],
                ['name', 'price', 'id', 'created'],
            ],
            [
                'asc_name,desc_price,DESC(id),asc_created',
                [
                    'name' => 'asc',
                    'price' => 'desc',
                    'id' => 'desc',
                    'created' => 'asc',
                ],
                ['name', 'price', 'id', 'created'],
            ],
            [
                'asc_name, desc_prrrice, ASC(id), asc_created',
                [
                    'name' => 'asc',
                    'id' => 'asc',
                    'created' => 'asc',
                ],
                ['name', 'price', 'id', 'created'],
            ],
            [
                'asc_namedesc_priceASC(id)asc_created',
                [
                    'name' => 'asc',
                    'price' => 'desc',
                    'id' => 'asc',
                    'created' => 'asc',
                ],
                ['name', 'price', 'id', 'created'],
            ],
            [
                'asc_name,asc(price),asc_id asc_created',
                [
                    'name' => 'asc',
                    'price' => 'asc',
                    'id' => 'asc',
                    'created' => 'asc',
                ],
                ['name', 'price', 'id', 'created'],
            ],
            [
                'DESC(name)DESC(price)ASC(id), asc_created',
                [
                    'name' => 'desc',
                    'price' => 'desc',
                    'id' => 'asc',
                    'created' => 'asc',
                ],
                ['name', 'price', 'id', 'created'],
            ],
            [
                'asc_nafme, desc_price, ASC(id), asc__created',
                [
                    'price' => 'desc',
                    'id' => 'asc',
                ],
                ['name', 'price', 'id', 'created'],
            ],
            [
                'dfgdfDdsasc_nameghdesc_fggfdesc_price, ASC(id), asc_created',
                [
                    'name' => 'asc',
                    'price' => 'desc',
                    'id' => 'asc',
                    'created' => 'asc',
                ],
                ['name', 'price', 'id', 'created'],
            ],
            [
                'asc_id, asc_price, asc_created',
                [
                    'id' => 'asc',
                    'price' => 'asc',
                    'created' => 'asc',
                ],
                ['name', 'price', 'id', 'created'],
            ],
            [
                'asc_fid, asc_ddprice, asc_jkcreated',
                [
                    'created' => 'desc',
                ],
                ['name', 'price', 'id', 'created'],
            ],
            [
                '',
                [
                    'created' => 'desc',
                ],
                ['name', 'price', 'id', 'created'],
            ],
            [
                'asc_id, asc_price, asc_created, asc_imgLinks, desc_imgLinksArr',
                [
                    'id' => 'asc',
                    'price' => 'asc',
                    'created' => 'asc',
                ],
                ['name', 'price', 'id', 'created'],
            ],
        ];
    }

    public function getUrl()
    {
        return [
            ['http://example.com'],
            ['http://example'],
            ['http://example.co'],
        ];
    }

    public function getPaymentData()
    {
        return [
            [21323.6, 'Тестовый платеж', 'testPaymentOrder0', 'http://example.com', null],
            [1232.1, 'Тестовый платеж', 'testPaymentOrder1', 'http://example.com', null],
            [21323.00, 'Тестовый платеж', 'testPaymentOrder2', 'http://example.com', 'Success'],
            [21323.01, 'Тестовый платеж', 'testPaymentOrder3', 'http://example.com', 'Cancelled'],
            [2123.0, 'Тестовый платеж', '6983da1c-19d3-402c-b052-2d6330b38020', 'http://example', 'Success'],
        ];
    }
}
