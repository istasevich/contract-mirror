<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\ContractReport;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ContractReport>
 */
final class ContractReportRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ContractReport::class);
    }

    public function save(ContractReport $report, bool $flush = false): void
    {
        $this->getEntityManager()->persist($report);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function findOneByPublicId(string $publicId): ?ContractReport
    {
        return $this->findOneBy(['publicId' => $publicId]);
    }
}
