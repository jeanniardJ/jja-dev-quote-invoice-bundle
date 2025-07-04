<?php

namespace JjaDev\QuoteInvoiceBundle\Service;

use JjaDev\QuoteInvoiceBundle\Entity\Quote;
use JjaDev\QuoteInvoiceBundle\Entity\Invoice;
use Doctrine\ORM\EntityManagerInterface;

class NumberGeneratorService
{
    public function __construct(
        private EntityManagerInterface $entityManager
    ) {
    }

    public function generateQuoteNumber(string $format = 'DEVIS-%06d'): string
    {
        $lastQuote = $this->entityManager->getRepository(Quote::class)
            ->createQueryBuilder('q')
            ->orderBy('q.id', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();

        $nextNumber = $lastQuote ? $lastQuote->getId() + 1 : 1;

        // Keep trying until we find a unique number
        do {
            $number = sprintf($format, $nextNumber);
            $exists = $this->entityManager->getRepository(Quote::class)
                ->findOneBy(['number' => $number]);
            
            if (!$exists) {
                return $number;
            }
            
            $nextNumber++;
        } while (true);
    }

    public function generateInvoiceNumber(string $format = 'FACT-%06d'): string
    {
        $lastInvoice = $this->entityManager->getRepository(Invoice::class)
            ->createQueryBuilder('i')
            ->orderBy('i.id', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();

        $nextNumber = $lastInvoice ? $lastInvoice->getId() + 1 : 1;

        // Keep trying until we find a unique number
        do {
            $number = sprintf($format, $nextNumber);
            $exists = $this->entityManager->getRepository(Invoice::class)
                ->findOneBy(['number' => $number]);
            
            if (!$exists) {
                return $number;
            }
            
            $nextNumber++;
        } while (true);
    }

    public function generateCustomNumber(string $prefix, int $length = 6, string $suffix = ''): string
    {
        $timestamp = time();
        $random = str_pad(mt_rand(0, pow(10, $length) - 1), $length, '0', STR_PAD_LEFT);
        
        return $prefix . $timestamp . $random . $suffix;
    }
}