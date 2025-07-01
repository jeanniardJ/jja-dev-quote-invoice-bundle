<?php

namespace JjaDev\QuoteInvoiceBundle\Service;

use JjaDev\QuoteInvoiceBundle\Entity\Quote;
use JjaDev\QuoteInvoiceBundle\Entity\Invoice;
use JjaDev\QuoteInvoiceBundle\Entity\InvoiceItem;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

class QuoteService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private NumberGeneratorService $numberGenerator,
        #[Autowire('%jja_dev_quote_invoice.quote_number_format%')]
        private string $quoteNumberFormat
    ) {
    }

    public function createQuote(Quote $quote): Quote
    {
        if (!$quote->getNumber()) {
            $quote->setNumber($this->numberGenerator->generateQuoteNumber($this->quoteNumberFormat));
        }

        $this->entityManager->persist($quote);
        $this->entityManager->flush();

        return $quote;
    }

    public function updateQuote(Quote $quote): Quote
    {
        $quote->setUpdatedAt(new \DateTimeImmutable());
        $this->entityManager->flush();

        return $quote;
    }

    public function deleteQuote(Quote $quote): void
    {
        if ($quote->getInvoice()) {
            throw new \InvalidArgumentException('Cannot delete a quote that has been converted to an invoice');
        }

        $this->entityManager->remove($quote);
        $this->entityManager->flush();
    }

    public function duplicateQuote(Quote $originalQuote): Quote
    {
        $newQuote = new Quote();
        $newQuote->setClient($originalQuote->getClient());
        $newQuote->setSubject($originalQuote->getSubject() . ' (Copie)');
        $newQuote->setNotes($originalQuote->getNotes());
        $newQuote->setTerms($originalQuote->getTerms());
        $newQuote->setDiscountAmount($originalQuote->getDiscountAmount());
        $newQuote->setDiscountPercentage($originalQuote->getDiscountPercentage());
        $newQuote->setIssueDate(new \DateTime());
        $newQuote->setValidUntil((new \DateTime())->modify('+30 days'));

        // Duplicate items
        foreach ($originalQuote->getItems() as $originalItem) {
            $newItem = new \JjaDev\QuoteInvoiceBundle\Entity\QuoteItem();
            $newItem->setProduct($originalItem->getProduct());
            $newItem->setDescription($originalItem->getDescription());
            $newItem->setQuantity($originalItem->getQuantity());
            $newItem->setUnitPrice($originalItem->getUnitPrice());
            $newItem->setUnit($originalItem->getUnit());
            $newItem->setVatRate($originalItem->getVatRate());
            $newItem->setDiscountPercentage($originalItem->getDiscountPercentage());
            $newItem->setSortOrder($originalItem->getSortOrder());
            
            $newQuote->addItem($newItem);
        }

        return $this->createQuote($newQuote);
    }

    public function convertToInvoice(Quote $quote): Invoice
    {
        if (!$quote->canBeConverted()) {
            throw new \InvalidArgumentException('Quote cannot be converted to invoice');
        }

        $invoice = new Invoice();
        $invoice->setClient($quote->getClient());
        $invoice->setQuote($quote);
        $invoice->setSubject($quote->getSubject());
        $invoice->setNotes($quote->getNotes());
        $invoice->setPaymentTerms($quote->getTerms());
        $invoice->setDiscountAmount($quote->getDiscountAmount());
        $invoice->setDiscountPercentage($quote->getDiscountPercentage());
        $invoice->setIssueDate(new \DateTime());
        $invoice->setDueDate((new \DateTime())->modify('+30 days'));

        // Convert quote items to invoice items
        foreach ($quote->getItems() as $quoteItem) {
            $invoiceItem = new InvoiceItem();
            $invoiceItem->setProduct($quoteItem->getProduct());
            $invoiceItem->setDescription($quoteItem->getDescription());
            $invoiceItem->setQuantity($quoteItem->getQuantity());
            $invoiceItem->setUnitPrice($quoteItem->getUnitPrice());
            $invoiceItem->setUnit($quoteItem->getUnit());
            $invoiceItem->setVatRate($quoteItem->getVatRate());
            $invoiceItem->setDiscountPercentage($quoteItem->getDiscountPercentage());
            $invoiceItem->setSortOrder($quoteItem->getSortOrder());
            
            $invoice->addItem($invoiceItem);
        }

        // Update quote status
        $quote->setStatus(Quote::STATUS_CONVERTED);
        $quote->setUpdatedAt(new \DateTimeImmutable());

        $this->entityManager->persist($invoice);
        $this->entityManager->flush();

        return $invoice;
    }

    public function updateStatus(Quote $quote, string $status): Quote
    {
        $quote->setStatus($status);
        $quote->setUpdatedAt(new \DateTimeImmutable());
        
        $this->entityManager->flush();

        return $quote;
    }

    public function getQuotesByStatus(string $status): array
    {
        return $this->entityManager->getRepository(Quote::class)
            ->findBy(['status' => $status], ['createdAt' => 'DESC']);
    }

    public function getExpiredQuotes(): array
    {
        return $this->entityManager->getRepository(Quote::class)
            ->createQueryBuilder('q')
            ->where('q.validUntil < :now')
            ->andWhere('q.status NOT IN (:excludedStatuses)')
            ->setParameter('now', new \DateTime())
            ->setParameter('excludedStatuses', [Quote::STATUS_ACCEPTED, Quote::STATUS_CONVERTED, Quote::STATUS_REJECTED])
            ->getQuery()
            ->getResult();
    }
}