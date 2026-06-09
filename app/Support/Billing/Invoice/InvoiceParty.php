<?php

namespace App\Support\Billing\Invoice;

class InvoiceParty
{
    public function __construct(
        public readonly string $name,
        public readonly string $taxId,
        public readonly string $address,
        public readonly string $country = 'RO',
    ) {
    }
}
