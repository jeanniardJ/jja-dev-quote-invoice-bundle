<?php

namespace JjaDev\QuoteInvoiceBundle\Controller;

use JjaDev\QuoteInvoiceBundle\Service\InvoiceService;
use JjaDev\QuoteInvoiceBundle\Service\QuoteService;
use JjaDev\QuoteInvoiceBundle\Repository\QuoteRepository;
use JjaDev\QuoteInvoiceBundle\Repository\InvoiceRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/quote-invoice', name: 'jja_dev_quote_invoice_')]
class DashboardController extends AbstractController
{
    public function __construct(
        private QuoteRepository $quoteRepository,
        private InvoiceRepository $invoiceRepository,
        private InvoiceService $invoiceService
    ) {
    }

    #[Route('/', name: 'dashboard')]
    public function index(): Response
    {
        $quoteStats = $this->quoteRepository->getQuoteStats();
        $invoiceStats = $this->invoiceRepository->getInvoiceStats();
        $dashboardStats = $this->invoiceService->getDashboardStats();

        $recentQuotes = $this->quoteRepository->findBy([], ['createdAt' => 'DESC'], 5);
        $recentInvoices = $this->invoiceRepository->findBy([], ['createdAt' => 'DESC'], 5);
        
        $overdueInvoices = $this->invoiceRepository->findOverdue();
        $expiredQuotes = $this->quoteRepository->findExpired();

        return $this->render('@JjaDevQuoteInvoice/dashboard/index.html.twig', [
            'quote_stats' => $quoteStats,
            'invoice_stats' => $invoiceStats,
            'dashboard_stats' => $dashboardStats,
            'recent_quotes' => $recentQuotes,
            'recent_invoices' => $recentInvoices,
            'overdue_invoices' => $overdueInvoices,
            'expired_quotes' => $expiredQuotes,
        ]);
    }
}