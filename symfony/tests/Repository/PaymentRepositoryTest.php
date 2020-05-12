<?php

namespace App\Tests\Repository;

use App\DataFixtures\PaymentFixtures;
use App\Entity\Payment;
use App\Repository\PaymentRepository;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class PaymentRepositoryTest extends KernelTestCase
{
    /**
     * @var \Doctrine\ORM\EntityManager
     */
    private $entityManager;

    protected function setUp()
    {
        $kernel = self::bootKernel();

        $this->entityManager = $kernel->getContainer()
            ->get('doctrine')
            ->getManager();
    }

    protected function tearDown(): void
    {
        $this->entityManager->close();
        $this->entityManager = null; // avoid memory leaks
        parent::tearDown();
    }

    /**
     * @dataProvider getPeriodData()
     * @param string $startsOn
     * @param string $endsOn
     * @param int $count
     * @throws \Exception
     */
    public function testFindByPeriod(string $startsOn, string $endsOn, int $count)
    {
        $startsOn = new \DateTime($startsOn);
        $endsOn = new \DateTime($endsOn);
        $fixture = new PaymentFixtures();
        $fixture->load($this->entityManager);

        $repository = $this->entityManager->getRepository(Payment::class);

        $paymentsFromRep = $repository->findByPeriod($startsOn, $endsOn);
        $this->assertCount($count, $paymentsFromRep);
        $this->assertEquals($count, $repository->countByPeriod($startsOn, $endsOn));

        foreach ($paymentsFromRep as $payment) {
            $this->assertTrue(($payment->getCreatedAt() > $startsOn) and ($payment->getCreatedAt() < $endsOn));
        }
    }

    public function testFindByPeriodWithOrder()
    {
        $startsOn = new \DateTime('2020-04-20 12:00:00');
        $endsOn = new \DateTime('2020-04-29 15:00:00');
        $fixture = new PaymentFixtures();
        $fixture->load($this->entityManager);

        $paymentsFromRep = $this->entityManager->getRepository(Payment::class)->findByPeriod(
            $startsOn,
            $endsOn,
            ['orderId' => 'desc']
        );

        $oldPayment = null;
        foreach ($paymentsFromRep as $payment) {
            $this->assertTrue(($payment->getCreatedAt() > $startsOn) and ($payment->getCreatedAt() < $endsOn));
            if(!is_null($oldPayment)){
                $this->assertTrue($payment->getOrderId() < $oldPayment->getOrderId());
            }
            $oldPayment = $payment;
        }
    }


    public function getPeriodData()
    {
        return [
            ['2020-04-20 12:00:00', '2020-04-29 15:00:00', 10],
            ['2020-04-22 12:00:00', '2020-04-29 15:00:00', 8],
            ['2020-04-20 12:00:00', '2020-04-29 15:00:00', 10],
            ['2020-04-28 12:00:00', '2020-04-29 12:00:00', 1],
            ['2020-04-20 12:00:00', '2020-04-29 12:00:00', 9],
            ['2020-04-22 12:00:00', '2020-04-23 12:00:00', 1],
        ];
    }
}
