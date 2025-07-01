<?php

namespace JjaDev\QuoteInvoiceBundle\Service;

use JjaDev\QuoteInvoiceBundle\Entity\Invoice;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

class InvoiceService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private NumberGeneratorService $numberGenerator,
        #[Autowire('%jja_dev_quote_invoice.invoice_number_format%')]
        private string $invoiceNumberFormat
    ) {
    }

    public function createInvoice(Invoice $invoice): Invoice
    {
        if (!$invoice->getNumber()) {
            $invoice->setNumber($this->numberGenerator->generateInvoiceNumber($this->invoiceNumberFormat));
        }

        $this->entityManager->persist($invoice);
        $this->entityManager->flush();

        return $invoice;
    }

    public function updateInvoice(Invoice $invoice): Invoice
    {
        $invoice->setUpdatedAt(new \DateTimeImmutable());
        $this->entityManager->flush();

        return $invoice;
    }

    public function deleteInvoice(Invoice $invoice): void
    {
        if ($invoice->getStatus() === Invoice::STATUS_PAID) {
            throw new \InvalidArgumentException('Cannot delete a paid invoice');
        }

        $this->entityManager->remove($invoice);
        $this->entityManager->flush();
    }

    public function markAsPaid(Invoice $invoice, \DateTimeInterface $paidDate = null): Invoice
    {
        $invoice->markAsPaid($paidDate);
        $this->entityManager->flush();

        return $invoice;
    }

    public function updateStatus(Invoice $invoice, string $status): Invoice
    {
        $invoice->setStatus($status);
        $invoice->setUpdatedAt(new \DateTimeImmutable());
        
        $this->entityManager->flush();

        return $invoice;
    }

    public function getInvoicesByStatus(string $status): array
    {
        return $this->entityManager->getRepository(Invoice::class)
            ->findBy(['status' => $status], ['createdAt' => 'DESC']);
    }

    public function getOverdueInvoices(): array
    {
        return $this->entityManager->getRepository(Invoice::class)
            ->createQueryBuilder('i')
            ->where('i.dueDate < :now')
            ->andWhere('i.status NOT IN (:excludedStatuses)')
            ->setParameter('now', new \DateTime())
            ->setParameter('excludedStatuses', [Invoice::STATUS_PAID, Invoice::STATUS_CANCELLED])
            ->getQuery()
            ->getResult();
    }

    public function getUnpaidInvoices(): array
    {
        return $this->entityManager->getRepository(Invoice::class)
            ->createQueryBuilder('i')
            ->where('i.status != :paidStatus')
            ->andWhere('i.status != :cancelledStatus')
            ->setParameter('paidStatus', Invoice::STATUS_PAID)
            ->setParameter('cancelledStatus', Invoice::STATUS_CANCELLED)
            ->orderBy('i.dueDate', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function getTotalRevenue(\DateTimeInterface $startDate = null, \DateTimeInterface $endDate = null): float
    {
        $qb = $this->entityManager->getRepository(Invoice::class)
            ->createQueryBuilder('i')
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

    public function getMonthlyRevenue(int $year, int $month): float
    {
        $startDate = new \DateTime(sprintf('%d-%02d-01', $year, $month));
        $endDate = (clone $startDate)->modify('last day of this month');

        return $this->getTotalRevenue($startDate, $endDate);
    }

    public function getDashboardStats(): array
    {
        $now = new \DateTime();
        $thisMonth = new \DateTime('first day of this month');
        $lastMonth = new \DateTime('first day of last month');
        $lastMonthEnd = new \DateTime('last day of last month');

        return [
            'total_invoices' => $this->entityManager->getRepository(Invoice::class)->count([]),
            'paid_invoices' => $this->entityManager->getRepository(Invoice::class)->count(['status' => Invoice::STATUS_PAID]),
            'overdue_invoices' => count($this->getOverdueInvoices()),
            'unpaid_invoices' => count($this->getUnpaidInvoices()),
            'total_revenue' => $this->getTotalRevenue(),
            'monthly_revenue' => $this->getTotalRevenue($thisMonth, $now),
            'last_month_revenue' => $this->getTotalRevenue($lastMonth, $lastMonthEnd),
        ];
    }
}