<?php

namespace JjaDev\QuoteInvoiceBundle\Repository;

use JjaDev\QuoteInvoiceBundle\Entity\Invoice;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\ORM\QueryBuilder;

/**
 * @extends ServiceEntityRepository<Invoice>
 */
class InvoiceRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Invoice::class);
    }

    public function findByStatus(string $status): array
    {
        return $this->createQueryBuilder('i')
            ->where('i.status = :status')
            ->setParameter('status', $status)
            ->orderBy('i.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findOverdue(): array
    {
        return $this->createQueryBuilder('i')
            ->where('i.dueDate < :now')
            ->andWhere('i.status NOT IN (:excludedStatuses)')
            ->setParameter('now', new \DateTime())
            ->setParameter('excludedStatuses', [Invoice::STATUS_PAID, Invoice::STATUS_CANCELLED])
            ->orderBy('i.dueDate', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findUnpaid(): array
    {
        return $this->createQueryBuilder('i')
            ->where('i.status != :paidStatus')
            ->andWhere('i.status != :cancelledStatus')
            ->setParameter('paidStatus', Invoice::STATUS_PAID)
            ->setParameter('cancelledStatus', Invoice::STATUS_CANCELLED)
            ->orderBy('i.dueDate', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findByClient(int $clientId): array
    {
        return $this->createQueryBuilder('i')
            ->where('i.client = :clientId')
            ->setParameter('clientId', $clientId)
            ->orderBy('i.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findBySearchTerm(string $searchTerm): array
    {
        return $this->createQueryBuilder('i')
            ->leftJoin('i.client', 'c')
            ->where('i.number LIKE :search')
            ->orWhere('i.subject LIKE :search')
            ->orWhere('c.name LIKE :search')
            ->orWhere('c.companyName LIKE :search')
            ->setParameter('search', '%' . $searchTerm . '%')
            ->orderBy('i.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function getTotalRevenue(\DateTimeInterface $startDate = null, \DateTimeInterface $endDate = null): float
    {
        $qb = $this->createQueryBuilder('i')
            ->select('SUM(
                CASE 
                    WHEN i.discountAmount IS NOT NULL THEN 
                        (SELECT SUM(ii.quantity * ii.unitPrice) FROM JjaDev\QuoteInvoiceBundle\Entity\InvoiceItem ii WHERE ii.invoice = i.id) - i.discountAmount
                    WHEN i.discountPercentage IS NOT NULL THEN 
                        (SELECT SUM(ii.quantity * ii.unitPrice) FROM JjaDev\QuoteInvoiceBundle\Entity\InvoiceItem ii WHERE ii.invoice = i.id) * (1 - i.discountPercentage / 100)
                    ELSE 
                        (SELECT SUM(ii.quantity * ii.unitPrice) FROM JjaDev\QuoteInvoiceBundle\Entity\InvoiceItem ii WHERE ii.invoice = i.id)
                END
            )')
            ->where('i.status = :paidStatus')
            ->setParameter('paidStatus', Invoice::STATUS_PAID);

        if ($startDate) {
            $qb->andWhere('i.paidDate >= :startDate')
               ->setParameter('startDate', $startDate);
        }

        if ($endDate) {
            $qb->andWhere('i.paidDate <= :endDate')
               ->setParameter('endDate', $endDate);
        }

        return (float) $qb->getQuery()->getSingleScalarResult();
    }

    public function getInvoiceStats(): array
    {
        $qb = $this->createQueryBuilder('i');
        
        $statusStats = $qb->select('i.status, COUNT(i.id) as count')
            ->groupBy('i.status')
            ->getQuery()
            ->getResult();

        $totalValue = $qb->select('SUM(
                CASE 
                    WHEN i.discountAmount IS NOT NULL THEN 
                        (SELECT SUM(ii.quantity * ii.unitPrice) FROM JjaDev\QuoteInvoiceBundle\Entity\InvoiceItem ii WHERE ii.invoice = i.id) - i.discountAmount
                    WHEN i.discountPercentage IS NOT NULL THEN 
                        (SELECT SUM(ii.quantity * ii.unitPrice) FROM JjaDev\QuoteInvoiceBundle\Entity\InvoiceItem ii WHERE ii.invoice = i.id) * (1 - i.discountPercentage / 100)
                    ELSE 
                        (SELECT SUM(ii.quantity * ii.unitPrice) FROM JjaDev\QuoteInvoiceBundle\Entity\InvoiceItem ii WHERE ii.invoice = i.id)
                END
            )')
            ->getQuery()
            ->getSingleScalarResult();

        return [
            'by_status' => $statusStats,
            'total_value' => (float) $totalValue,
        ];
    }

    public function createQueryBuilderForSearch(): QueryBuilder
    {
        return $this->createQueryBuilder('i')
            ->leftJoin('i.client', 'c')
            ->orderBy('i.createdAt', 'DESC');
    }
}