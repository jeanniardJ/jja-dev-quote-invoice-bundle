<?php

namespace JjaDev\QuoteInvoiceBundle\Tests\Unit\Service;

use JjaDev\QuoteInvoiceBundle\Entity\Quote;
use JjaDev\QuoteInvoiceBundle\Entity\Client;
use JjaDev\QuoteInvoiceBundle\Service\QuoteService;
use JjaDev\QuoteInvoiceBundle\Service\NumberGeneratorService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;

class QuoteServiceTest extends TestCase
{
    private EntityManagerInterface|MockObject $entityManager;
    private NumberGeneratorService|MockObject $numberGenerator;
    private QuoteService $quoteService;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->numberGenerator = $this->createMock(NumberGeneratorService::class);
        
        $this->quoteService = new QuoteService(
            $this->entityManager,
            $this->numberGenerator,
            'DEVIS-%06d'
        );
    }

    public function testCreateQuoteGeneratesNumber(): void
    {
        $quote = new Quote();
        $client = new Client();
        $client->setName('Test Client');
        $quote->setClient($client);

        $this->numberGenerator
            ->expects($this->once())
            ->method('generateQuoteNumber')
            ->with('DEVIS-%06d')
            ->willReturn('DEVIS-000001');

        $this->entityManager
            ->expects($this->once())
            ->method('persist')
            ->with($quote);

        $this->entityManager
            ->expects($this->once())
            ->method('flush');

        $result = $this->quoteService->createQuote($quote);

        $this->assertEquals('DEVIS-000001', $result->getNumber());
    }

    public function testCreateQuoteDoesNotOverrideExistingNumber(): void
    {
        $quote = new Quote();
        $client = new Client();
        $client->setName('Test Client');
        $quote->setClient($client);
        $quote->setNumber('CUSTOM-001');

        $this->numberGenerator
            ->expects($this->never())
            ->method('generateQuoteNumber');

        $this->entityManager
            ->expects($this->once())
            ->method('persist')
            ->with($quote);

        $this->entityManager
            ->expects($this->once())
            ->method('flush');

        $result = $this->quoteService->createQuote($quote);

        $this->assertEquals('CUSTOM-001', $result->getNumber());
    }

    public function testUpdateQuoteSetsUpdatedAt(): void
    {
        $quote = new Quote();
        $client = new Client();
        $client->setName('Test Client');
        $quote->setClient($client);

        $this->entityManager
            ->expects($this->once())
            ->method('flush');

        $result = $this->quoteService->updateQuote($quote);

        $this->assertInstanceOf(\DateTimeImmutable::class, $result->getUpdatedAt());
    }

    public function testDeleteQuoteThrowsExceptionWhenConverted(): void
    {
        $quote = new Quote();
        $invoice = $this->createMock(\JjaDev\QuoteInvoiceBundle\Entity\Invoice::class);
        $quote->setInvoice($invoice);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Cannot delete a quote that has been converted to an invoice');

        $this->quoteService->deleteQuote($quote);
    }
}