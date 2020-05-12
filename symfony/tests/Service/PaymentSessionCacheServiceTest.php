<?php

namespace App\Tests\Service;

use App\Service\PaymentSessionCacheService;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class PaymentSessionCacheServiceTest extends WebTestCase
{

    /**
     * @dataProvider getPaymentIds
     * @param $uid
     */
    public function testNonexistentPaymentSession($uid)
    {
        self::bootKernel();
        $container = self::$container->get(PaymentSessionCacheService::class);

        $this->assertEquals(false, $container->getPaymentId($uid));

    }


    /**
     * @dataProvider getPaymentIds
     * @param $id
     */
    public function testPaymentSession($id)
    {
        self::bootKernel();

        $container = self::$container->get(PaymentSessionCacheService::class);

        $uid = $container->createPaymentSession($id);

        $this->assertEquals($id, $container->getPaymentId($uid));
        $this->assertEquals(true, $container->deletePaymentSession($uid));
    }


    public function testDeletePaymentSession()
    {
        self::bootKernel();

        $container = self::$container->get(PaymentSessionCacheService::class);
        $this->assertEquals(true, $container->deletePaymentSession('ce2f3158-1f7b-4d49-a278-9358bb726bb3'));
    }

    public function getPaymentIds()
    {
        return [[1231], [2566], [3980], [123], [54564], [123321], [455456]];
    }


}
