<?php

namespace App\Tests\Controller;

use App\Controller\ApiController;

use App\DataFixtures\PaymentFixtures;
use App\Entity\Payment;
use App\Service\EntityServices\PaymentEntityService;
use Doctrine\ORM\EntityManager;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class ApiControllerTest extends WebTestCase
{
    private $client;

    /**
     * @var EntityManager
     */
    private $entityManager;

    public function setUp()
    {
        $this->client = static::createClient();
        $container = self::$container;
        $this->entityManager = $container
            ->get('doctrine')
            ->getManager();
        parent::setUp();
    }

    public function tearDown(): void
    {
        // doing this is recommended to avoid memory leaks
        $this->entityManager->close();
        $this->entityManager = null;
        parent::tearDown();
    }

    public function testCreatePaymentSessionSuccess()
    {
        $orderId = 'testOrderId1';
        $fixture = new PaymentFixtures();
        $payments = $fixture->load($this->entityManager);
        $this->client->request('GET', '/api/session/create/'.$orderId);
        $resp = $this->client->getResponse();
        $this->assertEquals(200, $resp->getStatusCode());
        $jsonData = json_decode($resp->getContent(), true);

        $container = self::$container->get(PaymentEntityService::class);
        $paymentBySessionId = $container->getPaymentBySessionId($jsonData['sessionId']);
        $this->assertEquals($payments[$orderId], $paymentBySessionId);
    }

    public function testCreatePaymentSessionAlreadyComplete()
    {
        $orderId = 'testOrderId25';
        $fixture = new PaymentFixtures();
        $fixture->load($this->entityManager);
        $this->client->request('GET', '/api/session/create/'.$orderId);
        $resp = $this->client->getResponse();
        $this->assertEquals(404, $resp->getStatusCode());
    }

    public function testCreatePaymentSessionNotFound()
    {
        $orderId = '123123123123123';
        $fixture = new PaymentFixtures();
        $fixture->load($this->entityManager);
        $this->client->request('GET', '/api/session/create/'.$orderId);
        $resp = $this->client->getResponse();
        $this->assertEquals(404, $resp->getStatusCode());
    }

    public function testGetPaymentsFromPeriodSuccess()
    {
        $id = 22;
        $fixture = new PaymentFixtures();
        $payments = $fixture->load($this->entityManager);

        $this->client->request(
            'GET',
            '/api/payments/period?startsOn=2020-04-'.$id.' 12:43:12&endsOn=2020-04-'.$id.'T15:43:12'
        );
        $resp = $this->client->getResponse();
        $this->assertEquals(200, $resp->getStatusCode());

        $jsonData = json_decode($resp->getContent(), true);
        $this->assertEquals($payments['testOrderId'.$id]->getAmount(), $jsonData['payments'][0]['amount']);
    }

    public function testGetPaymentsFromPeriodNotFound()
    {
        $id = 22;
        $fixture = new PaymentFixtures();
        $payments = $fixture->load($this->entityManager);

        $this->client->request(
            'GET',
            '/api/payments/period?startsOn=2020-04-'.$id.' 12:43:12&endsOn=2020-04-'.$id.'T15:43:12&page=10'
        );
        $resp = $this->client->getResponse();
        $this->assertEquals(200, $resp->getStatusCode());

        $jsonData = json_decode($resp->getContent(), true);
        $this->assertEquals($payments['testOrderId'.$id]->getAmount(), $jsonData['payments'][0]['amount']);
        $this->assertEquals(1, $jsonData['page']);
    }

    public function testGetPaymentsFromPeriodInvPage()
    {
        $id = 22;
        $fixture = new PaymentFixtures();
        $payments = $fixture->load($this->entityManager);

        $this->client->request(
            'GET',
            '/api/payments/period?startsOn=2020-04-30 12:43:12&endsOn=2020-04-29T15:43:12'
        );
        $resp = $this->client->getResponse();
        $this->assertEquals(404, $resp->getStatusCode());
    }

    /**
     * @dataProvider getInvalidPeriod
     * @param $startsOn
     * @param $endsOn
     */
    public function testGetPaymentsFromPeriodError($startsOn, $endsOn)
    {
        $this->client->request('GET', '/api/payments/period?startsOn='.$startsOn.'&endsOn='.$endsOn);
        $resp = $this->client->getResponse();
        $this->assertEquals(400, $resp->getStatusCode());
    }

    public function testGetPaymentSuccess()
    {
        $orderId = 'testOrderId25';
        $fixture = new PaymentFixtures();
        $payments = $fixture->load($this->entityManager);

        $this->client->request('GET', '/api/payment/'.$orderId.'?fields=notification');
        $resp = $this->client->getResponse();
        $this->assertEquals(200, $resp->getStatusCode());

        $jsonData = json_decode($resp->getContent(), true);
        $this->assertEquals($payments[$orderId]->getNotification(), $jsonData['payment']['notification']);
        $this->assertEquals($payments[$orderId]->getAmount(), $jsonData['payment']['amount']);
        $this->assertEquals($payments[$orderId]->getStatus(), $jsonData['payment']['status']);
    }

    public function testGetPaymentNotFound()
    {
        $orderId = 'testOrderIdNonExistent';

        $this->client->request('GET', '/api/payment/'.$orderId.'?fields=notification');
        $resp = $this->client->getResponse();
        $this->assertEquals(404, $resp->getStatusCode());
    }

    public function testRegisterSuccess()
    {
        $data = [
            'purpose' => 'TEST TEST',
            'amount' => 1500.76,
            'notification' => 'http://example',
            'orderId' => 'testId91212',
        ];
        $this->client->request('POST', '/api/payment/register', $data);
        $resp = $this->client->getResponse();
        $this->assertEquals(200, $resp->getStatusCode());
        $jsonData = json_decode($resp->getContent(), true);
        $container = self::$container->get(PaymentEntityService::class);

        $sessionIdPayment = $container->getPaymentBySessionId($jsonData['sessionId']);
        $paymentOrderId = $container->getPaymentByOrderId('testId91212');

        $this->assertSame($sessionIdPayment, $paymentOrderId);

        $this->entityManager->remove($paymentOrderId);
        $this->entityManager->flush();
    }

    public function testRegisterNotUnique()
    {
        $data = [
            'purpose' => 'TEST TEST',
            'amount' => 1500.76,
            'notification' => 'http://example',
            'orderId' => 'testId91212',
        ];
        $this->client->request('POST', '/api/payment/register', $data);
        $this->client->request('POST', '/api/payment/register', $data);
        $resp = $this->client->getResponse();
        $this->assertEquals(400, $resp->getStatusCode());

        $paymentOrderId = $this->entityManager->getRepository(Payment::class)->findOneBy(['orderId' => 'testId91212']);

        $this->entityManager->remove($paymentOrderId);
        $this->entityManager->flush();
    }


    public function getInvalidPeriod()
    {
        return [
            ['', ''],
            ['2020-03-31X14:43:12', '2020-03-31 14:43:12'],
            ['2020-03-31 14:43:12', '2020-03-31X14:43:12'],
            ['2020-03-3d14:43:12', '2020-0314:43:12'],
        ];
    }
}
