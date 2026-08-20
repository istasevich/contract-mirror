<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\ReportPayment;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ReportPayment>
 */
final class ReportPaymentRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ReportPayment::class);
    }

    public function save(ReportPayment $payment, bool $flush = false): void
    {
        $this->getEntityManager()->persist($payment);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }


    public function findOneById(string $id): ?ReportPayment
    {
        return $this->find($id);
    }
}
