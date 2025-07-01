<?php

namespace JjaDev\QuoteInvoiceBundle\Repository;

use JjaDev\QuoteInvoiceBundle\Entity\Client;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\ORM\QueryBuilder;

/**
 * @extends ServiceEntityRepository<Client>
 */
class ClientRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Client::class);
    }

    public function findActiveClients(): array
    {
        return $this->createQueryBuilder('c')
            ->where('c.isActive = :active')
            ->setParameter('active', true)
            ->orderBy('c.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findBySearchTerm(string $searchTerm): array
    {
        return $this->createQueryBuilder('c')
            ->where('c.name LIKE :search')
            ->orWhere('c.companyName LIKE :search')
            ->orWhere('c.email LIKE :search')
            ->setParameter('search', '%' . $searchTerm . '%')
            ->orderBy('c.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function getClientStats(Client $client): array
    {
        $qb = $this->getEntityManager()->createQueryBuilder();
        
        $quoteStats = $qb->select('COUNT(q.id) as quote_count, q.status')
            ->from('JjaDev\QuoteInvoiceBundle\Entity\Quote', 'q')
            ->where('q.client = :client')
            ->setParameter('client', $client)
            ->groupBy('q.status')
            ->getQuery()
            ->getResult();

        $invoiceStats = $qb->select('COUNT(i.id) as invoice_count, i.status')
            ->from('JjaDev\QuoteInvoiceBundle\Entity\Invoice', 'i')
            ->where('i.client = :client')
            ->setParameter('client', $client)
            ->groupBy('i.status')
            ->getQuery()
            ->getResult();

        return [
            'quotes' => $quoteStats,
            'invoices' => $invoiceStats,
        ];
    }

    public function createQueryBuilderForSearch(): QueryBuilder
    {
        return $this->createQueryBuilder('c')
            ->orderBy('c.name', 'ASC');
    }
}