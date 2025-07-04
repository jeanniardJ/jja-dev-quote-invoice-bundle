<?php

namespace JjaDev\QuoteInvoiceBundle\Repository;

use JjaDev\QuoteInvoiceBundle\Entity\Quote;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\ORM\QueryBuilder;

/**
 * @extends ServiceEntityRepository<Quote>
 */
class QuoteRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Quote::class);
    }

    public function findByStatus(string $status): array
    {
        return $this->createQueryBuilder('q')
            ->where('q.status = :status')
            ->setParameter('status', $status)
            ->orderBy('q.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findExpired(): array
    {
        return $this->createQueryBuilder('q')
            ->where('q.validUntil < :now')
            ->andWhere('q.status NOT IN (:excludedStatuses)')
            ->setParameter('now', new \DateTime())
            ->setParameter('excludedStatuses', [Quote::STATUS_ACCEPTED, Quote::STATUS_CONVERTED, Quote::STATUS_REJECTED])
            ->orderBy('q.validUntil', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findByClient(int $clientId): array
    {
        return $this->createQueryBuilder('q')
            ->where('q.client = :clientId')
            ->setParameter('clientId', $clientId)
            ->orderBy('q.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findBySearchTerm(string $searchTerm): array
    {
        return $this->createQueryBuilder('q')
            ->leftJoin('q.client', 'c')
            ->where('q.number LIKE :search')
            ->orWhere('q.subject LIKE :search')
            ->orWhere('c.name LIKE :search')
            ->orWhere('c.companyName LIKE :search')
            ->setParameter('search', '%' . $searchTerm . '%')
            ->orderBy('q.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function getQuoteStats(): array
    {
        $qb = $this->createQueryBuilder('q');
        
        $statusStats = $qb->select('q.status, COUNT(q.id) as count')
            ->groupBy('q.status')
            ->getQuery()
            ->getResult();

        $totalValue = $qb->select('SUM(
                CASE 
                    WHEN q.discountAmount IS NOT NULL THEN 
                        (SELECT SUM(qi.quantity * qi.unitPrice) FROM JjaDev\QuoteInvoiceBundle\Entity\QuoteItem qi WHERE qi.quote = q.id) - q.discountAmount
                    WHEN q.discountPercentage IS NOT NULL THEN 
                        (SELECT SUM(qi.quantity * qi.unitPrice) FROM JjaDev\QuoteInvoiceBundle\Entity\QuoteItem qi WHERE qi.quote = q.id) * (1 - q.discountPercentage / 100)
                    ELSE 
                        (SELECT SUM(qi.quantity * qi.unitPrice) FROM JjaDev\QuoteInvoiceBundle\Entity\QuoteItem qi WHERE qi.quote = q.id)
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
        return $this->createQueryBuilder('q')
            ->leftJoin('q.client', 'c')
            ->orderBy('q.createdAt', 'DESC');
    }
}