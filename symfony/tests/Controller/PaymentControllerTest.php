<?php

namespace App\Tests\Controller;

use App\DataFixtures\PaymentFixtures;
use App\Service\PaymentSessionCacheService;
use Doctrine\ORM\EntityManager;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class PaymentControllerTest extends WebTestCase
{
    /**
     * @var EntityManager
     */
    private $entityManager;

    private $client;

    private $payments;

    public function setUp()
    {
        $this->client = static::createClient();
        $container = self::$container;
        $doctrine = $container->get('doctrine');
        $this->entityManager = $doctrine->getManager();
        $fixture = new PaymentFixtures();
        $this->payments = $fixture->load($this->entityManager);

        parent::setUp();
    }

    public function tearDown(): void
    {

        // doing this is recommended to avoid memory leaks
        $this->entityManager->close();
        $this->entityManager = null;
        $this->payments = null;
        parent::tearDown();
    }

    /**
     * @dataProvider getInvalidSessionId
     * @param $sessionId
     */
    public function testPayCardFormInvalidSessionId($sessionId)
    {
        $this->client->request('GET', '/payment/card/form', ['sessionId' => $sessionId]);
        $this->assertEquals(400, $this->client->getResponse()->getStatusCode());
    }

    public function testPayCardFormNotFound()
    {
        $this->client->request('GET', '/payment/card/form', ['sessionId' => '6983da1c-19d3-402c-b052-2d6330b38020']);
        $this->assertEquals(404, $this->client->getResponse()->getStatusCode());
    }

    public function testPayCardForm()
    {
        $containerSession = self::$container->get(PaymentSessionCacheService::class);
        $payment = $this->payments['testOrderId0'];
        $uid = $containerSession->createPaymentSession($payment->getId());

        $this->client->request('GET', '/payment/card/form', ['sessionId' => $uid]);
        $this->assertEquals(200, $this->client->getResponse()->getStatusCode());
    }

    public function testSuccPayCard()
    {
        $containerSession = self::$container->get(PaymentSessionCacheService::class);
        $payment = $this->payments['testOrderId1'];
        $uid = $containerSession->createPaymentSession($payment->getId());


        $crawler = $this->client->request('GET', '/payment/card/form?sessionId='.$uid);
        $form = $crawler->selectButton('pay_b')->form();
        $form->setValues(
            [
                'card_payment' =>
                    [
                        'number' => '4111111111111111',
                        'cardholderName' => 'Cardholder Name',
                        'expiryDate' => '2012/04',
                        'securityNumber' => '123',
                    ],
            ]
        );

        $this->client->request($form->getMethod(), '/payment/card/form?sessionId='.$uid, $form->getPhpValues());

        $this->assertContains('Платеж успешно совершен', $this->client->getResponse()->getContent());

        $this->assertEquals(200, $this->client->getResponse()->getStatusCode());
    }


    public function getInvalidSessionId()
    {
        return [
            ['6983da1c19d3-402c-b052-2d6330b38020'],
            [''],
            ['6983da1c19d3-402c-b052-2d6330b3802077'],
            ['6983da1c19d3-402c-b05262d6330b38020'],
        ];
    }
}
