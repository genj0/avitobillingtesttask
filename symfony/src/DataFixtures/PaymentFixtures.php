<?php

namespace App\DataFixtures;

use App\Entity\Payment;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class PaymentFixtures extends Fixture
{
    /**
     * @param ObjectManager $manager
     * @return Payment[]
     */
    public function load(ObjectManager $manager): array
    {
        $payments = $manager->getRepository(Payment::class)->findAll();
        foreach ($payments as $payment)
        {
            $manager->remove($payment);
        }
        $manager->flush();
        $payments = [];
        for ($i = 0; $i < 20; $i++) {
            $payment = new Payment();
            $payment->setAmount(mt_rand(10,100000).".".mt_rand(0,99));
            $payment->setPurpose('Платеж '.$i);
            $payment->setOrderId('testOrderId'.$i);
            $payment->setCreatedAt(new \DateTime('2020-04-'.$i.' 14:43:12'));
            $manager->persist($payment);
            $payments[$payment->getOrderId()] = $payment;
        }
        for ($i = 20; $i < 30; $i++) {
            $payment = new Payment();
            $payment->setAmount(mt_rand(10,100000).".".mt_rand(0,99));
            $payment->setPurpose('Платеж '.$i);
            $payment->setStatus('Success');
            $payment->setOrderId('testOrderId'.$i);
            $payment->setCreatedAt(new \DateTime('2020-04-'.$i.' 14:43:12'));
            $manager->persist($payment);
            $payments[$payment->getOrderId()] = $payment;
        }
        $manager->flush();
        return $payments;
    }
}
