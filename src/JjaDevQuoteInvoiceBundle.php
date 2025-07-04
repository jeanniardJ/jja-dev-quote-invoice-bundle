<?php

namespace JjaDev\QuoteInvoiceBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;

class JjaDevQuoteInvoiceBundle extends Bundle
{
    public function getPath(): string
    {
        return \dirname(__DIR__);
    }
}