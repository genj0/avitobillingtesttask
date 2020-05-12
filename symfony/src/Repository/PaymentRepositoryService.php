<?php


namespace App\Repository;


use App\Entity\Payment;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ObjectRepository;

/**
 * @method Payment|null find($id, $lockMode = null, $lockVersion = null)
 * @method Payment|null findOneBy(array $criteria, array $orderBy = null)
 * @method Payment[]    findAll()
 * @method Payment[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
interface PaymentRepositoryService extends ObjectRepository
{
    /**
     * Finds Payments for a given period
     *
     * @param \DateTimeInterface $startsOn  Beginning of period
     * @param \DateTimeInterface $endsOn    End of period
     * @param array|null $orderBy
     * @param int|null $limit
     * @param int|null $offset
     * @return Payment[]                    Returns an array of {@link Payment} objects
     */
    public function findByPeriod(
        \DateTimeInterface $startsOn,
        \DateTimeInterface $endsOn,
        array $orderBy = null,
        int $limit = null,
        int $offset = null
    );

    /**
     * Getting the count of payments for a given period
     *
     * @param \DateTimeInterface $startsOn  Beginning of period
     * @param \DateTimeInterface $endsOn    End of period
     *
     * @return int                          Count of payments
     */
    public function countByPeriod(\DateTimeInterface $startsOn, \DateTimeInterface $endsOn): int;

}